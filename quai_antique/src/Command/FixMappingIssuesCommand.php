<?php

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:fix:mapping-issues',
    description: 'Diagnoses and fixes Doctrine mapping issues',
)]
class FixMappingIssuesCommand extends Command
{
    private $entityManager;
    private $projectDir;
    private $issuesFound = 0;
    private $issuesFixed = 0;

    public function __construct(EntityManagerInterface $entityManager, string $projectDir)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
    }

    protected function configure(): void
    {
        $this
            ->addOption('fix', null, InputOption::VALUE_NONE, 'Fix issues automatically')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be fixed without making changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Doctrine Mapping Issue Diagnostics');

        $fix = $input->getOption('fix');
        $dryRun = $input->getOption('dry-run');

        // Get all entity metadata
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $allMetadata = $metadataFactory->getAllMetadata();

        if (empty($allMetadata)) {
            $io->warning('No entity metadata found. Did you forget to create entities?');
            return Command::FAILURE;
        }

        $io->section('Analyzing entity mappings');
        $io->progressStart(count($allMetadata));

        // Build mapping of all entities and their relationships
        $entityMap = [];
        foreach ($allMetadata as $metadata) {
            $entityMap[$metadata->getName()] = [
                'metadata' => $metadata,
                'associations' => $metadata->getAssociationNames(),
            ];
            $io->progressAdvance();
        }
        $io->progressFinish();

        // Check for common issues
        $io->section('Checking for mapping issues');

        // 1. Check for inconsistent bidirectional associations
        $io->text('Checking bidirectional associations...');
        foreach ($entityMap as $entityClass => $entityData) {
            /** @var ClassMetadata $metadata */
            $metadata = $entityData['metadata'];
            
            foreach ($metadata->getAssociationMappings() as $fieldName => $mapping) {
                // Skip unidirectional associations
                if (!isset($mapping['inversedBy']) && !isset($mapping['mappedBy'])) {
                    continue;
                }

                $targetEntity = $mapping['targetEntity'];
                $targetMetadata = $metadataFactory->getMetadataFor($targetEntity);
                
                // Case 1: This entity owns the relationship (has inversedBy)
                if (isset($mapping['inversedBy'])) {
                    $inverseField = $mapping['inversedBy'];
                    
                    // Check if inverse side exists
                    if (!$targetMetadata->hasField($inverseField) && !$targetMetadata->hasAssociation($inverseField)) {
                        $this->issuesFound++;
                        $io->text("<fg=red>✗</> Missing inverse association in $targetEntity::$inverseField referred from $entityClass::$fieldName");
                        
                        if ($fix) {
                            $this->fixMissingInverseAssociation($targetEntity, $inverseField, $entityClass, $fieldName);
                            $this->issuesFixed++;
                            $io->text("  <fg=green>✓</> Fixed by adding the missing property");
                        } else if ($dryRun) {
                            $io->text("  <fg=yellow>Would fix by adding the missing property</>");
                        }
                    }
                    // Check if inverse side is correctly configured
                    elseif ($targetMetadata->hasAssociation($inverseField)) {
                        $inverseMapping = $targetMetadata->getAssociationMapping($inverseField);
                        if (!isset($inverseMapping['mappedBy']) || $inverseMapping['mappedBy'] !== $fieldName) {
                            $this->issuesFound++;
                            $io->text("<fg=red>✗</> Inconsistent bidirectional association between $entityClass::$fieldName and $targetEntity::$inverseField");
                            
                            if ($fix) {
                                $this->fixInconsistentBidirectionalAssociation($targetEntity, $inverseField, $fieldName);
                                $this->issuesFixed++;
                                $io->text("  <fg=green>✓</> Fixed inconsistent mapping");
                            } else if ($dryRun) {
                                $io->text("  <fg=yellow>Would fix inconsistent mapping</>");
                            }
                        }
                    }
                }
                
                // Case 2: This entity is the inverse side (has mappedBy)
                if (isset($mapping['mappedBy'])) {
                    $mappedField = $mapping['mappedBy'];
                    
                    // Check if owning side exists
                    if (!$targetMetadata->hasField($mappedField) && !$targetMetadata->hasAssociation($mappedField)) {
                        $this->issuesFound++;
                        $io->text("<fg=red>✗</> Missing owning association in $targetEntity::$mappedField referred from $entityClass::$fieldName");
                        
                        if ($fix) {
                            $this->fixMissingOwningAssociation($targetEntity, $mappedField, $entityClass, $fieldName);
                            $this->issuesFixed++;
                            $io->text("  <fg=green>✓</> Fixed by adding the missing property");
                        } else if ($dryRun) {
                            $io->text("  <fg=yellow>Would fix by adding the missing property</>");
                        }
                    }
                    // Check if owning side is correctly configured
                    elseif ($targetMetadata->hasAssociation($mappedField)) {
                        $owningMapping = $targetMetadata->getAssociationMapping($mappedField);
                        if (!isset($owningMapping['inversedBy']) || $owningMapping['inversedBy'] !== $fieldName) {
                            $this->issuesFound++;
                            $io->text("<fg=red>✗</> Inconsistent bidirectional association between $entityClass::$fieldName and $targetEntity::$mappedField");
                            
                            if ($fix) {
                                $this->fixInconsistentBidirectionalAssociation($targetEntity, $mappedField, $fieldName, true);
                                $this->issuesFixed++;
                                $io->text("  <fg=green>✓</> Fixed inconsistent mapping");
                            } else if ($dryRun) {
                                $io->text("  <fg=yellow>Would fix inconsistent mapping</>");
                            }
                        }
                    }
                }
            }
        }

        // 2. Check for invalid column types
        $io->text('Checking for invalid column types...');
        foreach ($entityMap as $entityClass => $entityData) {
            /** @var ClassMetadata $metadata */
            $metadata = $entityData['metadata'];
            
            foreach ($metadata->fieldMappings as $fieldName => $mapping) {
                // Check for common column type issues
                if ($mapping['type'] === 'datetime' && !class_exists('\Doctrine\DBAL\Types\DateTimeType')) {
                    $this->issuesFound++;
                    $io->text("<fg=red>✗</> Invalid datetime type in $entityClass::$fieldName, should be datetime_immutable");
                    
                    if ($fix) {
                        $this->fixFieldType($entityClass, $fieldName, 'datetime', 'datetime_immutable');
                        $this->issuesFixed++;
                        $io->text("  <fg=green>✓</> Fixed datetime type");
                    } else if ($dryRun) {
                        $io->text("  <fg=yellow>Would fix datetime type</>");
                    }
                }
            }
        }

        // 3. Check for invalid column mapping annotations
        $io->text('Checking for annotation/attribute issues...');
        foreach ($entityMap as $entityClass => $entityData) {
            $this->checkEntityAnnotations($entityClass, $io, $fix, $dryRun);
        }

        // Summary
        $io->section('Diagnostics Summary');
        
        if ($this->issuesFound === 0) {
            $io->success('No mapping issues found!');
        } else {
            $io->text("Found $this->issuesFound mapping issues.");
            
            if ($fix) {
                $io->text("Fixed $this->issuesFixed issues.");
                $io->caution('You may need to clear the cache for changes to take effect:');
                $io->text('php bin/console cache:clear');
            } else {
                $io->caution("Run with --fix option to automatically fix these issues.");
            }
        }

        return Command::SUCCESS;
    }

    private function fixMissingInverseAssociation(string $entityClass, string $fieldName, string $targetEntity, string $mappedByField): void
    {
        // This would modify the entity file to add the missing property
        $entityFile = $this->findEntityFile($entityClass);
        if (!$entityFile) {
            return;
        }
        
        $content = file_get_contents($entityFile);
        
        // Determine the type of association and create appropriate code
        $isCollection = $this->isCollectionAssociation($targetEntity, $mappedByField);
        
        if ($isCollection) {
            // Add Collection property
            $propertyCode = "\n    #[ORM\\OneToMany(mappedBy: '$fieldName', targetEntity: $targetEntity::class)]\n";
            $propertyCode .= "    private Collection \$$fieldName;\n";
            
            // Add constructor initialization if needed
            if (strpos($content, 'public function __construct') === false) {
                $propertyCode .= "\n    public function __construct()\n    {\n";
                $propertyCode .= "        \$this->$fieldName = new ArrayCollection();\n";
                $propertyCode .= "    }\n";
            } else {
                // Insert into existing constructor
                $content = preg_replace(
                    '/(public function __construct.*?\{)/s',
                    "$1\n        \$this->$fieldName = new ArrayCollection();",
                    $content
                );
            }
            
            // Add getter and adder/remover methods
            $propertyCode .= "\n    /**\n     * @return Collection<int, $targetEntity>\n     */\n";
            $propertyCode .= "    public function get" . ucfirst($fieldName) . "(): Collection\n    {\n";
            $propertyCode .= "        return \$this->$fieldName;\n    }\n\n";
            
            $singularName = $this->singularize($fieldName);
            
            $propertyCode .= "    public function add" . ucfirst($singularName) . "($targetEntity \$$singularName): self\n    {\n";
            $propertyCode .= "        if (!\$this->$fieldName->contains(\$$singularName)) {\n";
            $propertyCode .= "            \$this->$fieldName->add(\$$singularName);\n";
            $propertyCode .= "            \$$singularName->set" . ucfirst($mappedByField) . "(\$this);\n";
            $propertyCode .= "        }\n\n";
            $propertyCode .= "        return \$this;\n    }\n\n";
            
            $propertyCode .= "    public function remove" . ucfirst($singularName) . "($targetEntity \$$singularName): self\n    {\n";
            $propertyCode .= "        if (\$this->$fieldName->removeElement(\$$singularName)) {\n";
            $propertyCode .= "            // set the owning side to null (unless already changed)\n";
            $propertyCode .= "            if (\$$singularName->get" . ucfirst($mappedByField) . "() === \$this) {\n";
            $propertyCode .= "                \$$singularName->set" . ucfirst($mappedByField) . "(null);\n";
            $propertyCode .= "            }\n";
            $propertyCode .= "        }\n\n";
            $propertyCode .= "        return \$this;\n    }\n";
        } else {
            // Add ManyToOne property
            $propertyCode = "\n    #[ORM\\ManyToOne(inversedBy: '$mappedByField', targetEntity: $targetEntity::class)]\n";
            $propertyCode .= "    private ?" . basename($targetEntity) . " \$$fieldName = null;\n";
            
            // Add getter and setter
            $propertyCode .= "\n    public function get" . ucfirst($fieldName) . "(): ?" . basename($targetEntity) . "\n    {\n";
            $propertyCode .= "        return \$this->$fieldName;\n    }\n\n";
            
            $propertyCode .= "    public function set" . ucfirst($fieldName) . "(?" . basename($targetEntity) . " \$$fieldName): self\n    {\n";
            $propertyCode .= "        \$this->$fieldName = \$$fieldName;\n\n";
            $propertyCode .= "        return \$this;\n    }\n";
        }
        
        // Insert the property before the last closing brace
        $content = preg_replace('/}(?!\s*})$/', $propertyCode . "}", $content);
        
        // Add necessary use statements
        if ($isCollection) {
            if (strpos($content, 'use Doctrine\Common\Collections\Collection;') === false) {
                $content = preg_replace('/namespace.*?;/s', "$0\n\nuse Doctrine\\Common\\Collections\\Collection;", $content);
            }
            if (strpos($content, 'use Doctrine\Common\Collections\ArrayCollection;') === false) {
                $content = preg_replace('/namespace.*?;/s', "$0\n\nuse Doctrine\\Common\\Collections\\ArrayCollection;", $content);
            }
        }
        if (strpos($content, 'use ' . $targetEntity . ';') === false) {
            $content = preg_replace('/namespace.*?;/s', "$0\n\nuse $targetEntity;", $content);
        }
        
        file_put_contents($entityFile, $content);
    }

    private function fixMissingOwningAssociation(string $entityClass, string $fieldName, string $targetEntity, string $inversedByField): void
    {
        // This would modify the entity file to add the missing property
        $entityFile = $this->findEntityFile($entityClass);
        if (!$entityFile) {
            return;
        }
        
        $content = file_get_contents($entityFile);
        
        // Determine if this should be a ManyToOne or OneToOne
        $isToMany = $this->isCollectionAssociation($targetEntity, $inversedByField);
        
        if ($isToMany) {
            // Add ManyToOne relationship
            $propertyCode = "\n    #[ORM\\ManyToOne(inversedBy: '$inversedByField', targetEntity: $targetEntity::class)]\n";
            $propertyCode .= "    private ?" . basename($targetEntity) . " \$$fieldName = null;\n";
        } else {
            // Add OneToOne relationship
            $propertyCode = "\n    #[ORM\\OneToOne(inversedBy: '$inversedByField', targetEntity: $targetEntity::class)]\n";
            $propertyCode .= "    private ?" . basename($targetEntity) . " \$$fieldName = null;\n";
        }
        
        // Add getter and setter
        $propertyCode .= "\n    public function get" . ucfirst($fieldName) . "(): ?" . basename($targetEntity) . "\n    {\n";
        $propertyCode .= "        return \$this->$fieldName;\n    }\n\n";
        
        $propertyCode .= "    public function set" . ucfirst($fieldName) . "(?" . basename($targetEntity) . " \$$fieldName): self\n    {\n";
        $propertyCode .= "        \$this->$fieldName = \$$fieldName;\n\n";
        $propertyCode .= "        return \$this;\n    }\n";
        
        // Insert the property before the last closing brace
        $content = preg_replace('/}(?!\s*})$/', $propertyCode . "}", $content);
        
        // Add necessary use statement
        if (strpos($content, 'use ' . $targetEntity . ';') === false) {
            $content = preg_replace('/namespace.*?;/s', "$0\n\nuse $targetEntity;", $content);
        }
        
        file_put_contents($entityFile, $content);
    }

    private function fixInconsistentBidirectionalAssociation(string $entityClass, string $fieldName, string $mappedByOrInversedBy, bool $isOwning = false): void
    {
        $entityFile = $this->findEntityFile($entityClass);
        if (!$entityFile) {
            return;
        }
        
        $content = file_get_contents($entityFile);
        
        // Find the field annotation/attribute
        if (preg_match('/#\[ORM\\\\(OneToMany|ManyToOne|OneToOne|ManyToMany).*?'.$fieldName.'\s*[,=].*?\]/s', $content, $matches)) {
            $oldAnnotation = $matches[0];
            $relationType = $matches[1];
            
            // Create updated annotation with correct mappedBy or inversedBy
            if ($isOwning) {
                $newAnnotation = preg_replace('/inversedBy:\s*[\'"][^\'"]*[\'"]/', "inversedBy: '$mappedByOrInversedBy'", $oldAnnotation);
                if ($oldAnnotation === $newAnnotation) {
                    // If there was no inversedBy, add it
                    $newAnnotation = str_replace("]", ", inversedBy: '$mappedByOrInversedBy']", $oldAnnotation);
                }
            } else {
                $newAnnotation = preg_replace('/mappedBy:\s*[\'"][^\'"]*[\'"]/', "mappedBy: '$mappedByOrInversedBy'", $oldAnnotation);
                if ($oldAnnotation === $newAnnotation) {
                    // If there was no mappedBy, add it
                    $newAnnotation = str_replace("]", ", mappedBy: '$mappedByOrInversedBy']", $oldAnnotation);
                }
            }
            
            // Replace the annotation
            $content = str_replace($oldAnnotation, $newAnnotation, $content);
            file_put_contents($entityFile, $content);
        }
    }

    private function fixFieldType(string $entityClass, string $fieldName, string $oldType, string $newType): void
    {
        $entityFile = $this->findEntityFile($entityClass);
        if (!$entityFile) {
            return;
        }
        
        $content = file_get_contents($entityFile);
        
        // Find and replace the type in the field annotation/attribute
        if (preg_match('/#\[ORM\\\\Column.*?type:\s*[\'"]'.$oldType.'[\'"].*?\]/s', $content, $matches)) {
            $oldAnnotation = $matches[0];
            $newAnnotation = str_replace("type: '$oldType'", "type: '$newType'", $oldAnnotation);
            $newAnnotation = str_replace("type: \"$oldType\"", "type: \"$newType\"", $newAnnotation);
            
            $content = str_replace($oldAnnotation, $newAnnotation, $content);
            file_put_contents($entityFile, $content);
        }
    }

    private function checkEntityAnnotations(string $entityClass, SymfonyStyle $io, bool $fix, bool $dryRun): void
    {
        $entityFile = $this->findEntityFile($entityClass);
        if (!$entityFile) {
            return;
        }
        
        $content = file_get_contents($entityFile);
        
        // Check if using both annotations and attributes
        $hasAnnotations = preg_match('/@ORM\\\\/', $content);
        $hasAttributes = preg_match('/#\[ORM\\\\/', $content);
        
        if ($hasAnnotations && $hasAttributes) {
            $this->issuesFound++;
            $io->text("<fg=red>✗</> Entity $entityClass is using both annotations and attributes");
            
            if ($fix) {
                // Convert annotations to attributes
                $this->convertAnnotationsToAttributes($entityClass, $entityFile);
                $this->issuesFixed++;
                $io->text("  <fg=green>✓</> Converted annotations to attributes");
            } else if ($dryRun) {
                $io->text("  <fg=yellow>Would convert annotations to attributes</>");
            }
        }
    }

    private function convertAnnotationsToAttributes(string $entityClass, string $entityFile): void
    {
        // This would be a more complex operation to convert annotations to attributes
        // For now, we'll just alert the user that this needs to be done manually
        $content = file_get_contents($entityFile);
        
        // Simple replacements for common annotations
        $replacements = [
            '/**\s*\*\s*@ORM\\\\Entity' => '#[ORM\\Entity',
            '@ORM\\\\Column\(([^)]*)\)' => '#[ORM\\Column($1)]',
            '@ORM\\\\Id' => '#[ORM\\Id]',
            '@ORM\\\\GeneratedValue' => '#[ORM\\GeneratedValue]',
            '@ORM\\\\ManyToOne\(([^)]*)\)' => '#[ORM\\ManyToOne($1)]',
            '@ORM\\\\OneToMany\(([^)]*)\)' => '#[ORM\\OneToMany($1)]',
            '@ORM\\\\ManyToMany\(([^)]*)\)' => '#[ORM\\ManyToMany($1)]',
            '@ORM\\\\OneToOne\(([^)]*)\)' => '#[ORM\\OneToOne($1)]',
        ];
        
        foreach ($replacements as $pattern => $replacement) {
            $content = preg_replace("/$pattern/", $replacement, $content);
        }
        
        // Save modified content
        file_put_contents($entityFile, $content);
    }

    private function findEntityFile(string $entityClass): ?string
    {
        // Extract the simple class name
        $className = basename(str_replace('\\', '/', $entityClass));
        
        // Try to locate the file
        $entityDir = $this->projectDir . '/src/Entity';
        $entityFile = $entityDir . '/' . $className . '.php';
        
        if (file_exists($entityFile)) {
            return $entityFile;
        }
        
        return null;
    }

    private function isCollectionAssociation(string $entityClass, string $fieldName): bool
    {
        try {
            $metadata = $this->entityManager->getClassMetadata($entityClass);
            if ($metadata->hasAssociation($fieldName)) {
                return $metadata->isCollectionValuedAssociation($fieldName);
            }
        } catch (\Exception $e) {
            // If we can't determine it, assume it's not a collection
        }
        
        // If we can't determine, make a guess based on the field name (plurals are likely collections)
        return $this->isPlural($fieldName);
    }

    private function isPlural(string $name): bool
    {
        $lastChar = substr($name, -1);
        return $lastChar === 's' || $lastChar === 'i';  // Simple check for English plurals
    }

    private function singularize(string $plural): string
    {
        // Very simple singularization - just for common English patterns
        if (substr($plural, -1) === 's') {
            return substr($plural, 0, -1);
        }
        if (substr($plural, -3) === 'ies') {
            return substr($plural, 0, -3) . 'y';
        }
        if (substr($plural, -1) === 'i') {
            return substr($plural, 0, -1) . 'us';
        }
        return $plural;  // If we can't determine, return as is
    }
}
