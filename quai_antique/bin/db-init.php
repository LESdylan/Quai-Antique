<?php
/**
 * Quai Antique Restaurant - Database Initialization Script
 * 
 * This script creates all the necessary entities for the restaurant database
 * using direct entity file creation rather than Symfony commands.
 * 
 * Usage: php bin/db-init.php [--force]
 * 
 * Options:
 *  --force    Drop the database and recreate it from scratch
 */

// Execute shell command and return output
function executeCommand($command) {
    echo "Running: $command\n";
    $output = [];
    exec($command, $output, $returnCode);
    echo implode("\n", $output) . "\n";
    
    if ($returnCode !== 0) {
        echo "Command failed with code $returnCode\n";
    }
    return $returnCode === 0;
}

// Create entity file from template
function createEntityFile($projectDir, $entityName, $properties) {
    $entityDir = "$projectDir/src/Entity";
    $repositoryDir = "$projectDir/src/Repository";
    
    // Ensure directories exist
    if (!is_dir($entityDir)) {
        mkdir($entityDir, 0755, true);
    }
    
    if (!is_dir($repositoryDir)) {
        mkdir($repositoryDir, 0755, true);
    }
    
    // Generate properties code
    $propertiesCode = [];
    $gettersSettersCode = [];
    
    foreach ($properties as $name => $config) {
        $type = $config['type'];
        $nullable = $config['nullable'] ?? false;
        $length = $config['length'] ?? null;
        $typeHint = $type;
        
        // Adjust type hints for PHP
        switch ($type) {
            case 'datetime':
                $typeHint = '\DateTimeInterface';
                $type = 'datetime';
                break;
            case 'decimal':
                $typeHint = 'float';
                $type = 'decimal';
                break;
            case 'integer':
                $typeHint = 'int';
                break;
            case 'text':
            case 'string':
                $typeHint = 'string';
                break;
            case 'boolean':
                $typeHint = 'bool';
                $type = 'boolean';
                break;
            case 'json':
                $typeHint = 'array';
                break;
        }
        
        // Create ORM annotation
        $annotation = "    /**\n     * @ORM\\Column(";
        
        if ($type === 'string' || $type === 'decimal') {
            $annotation .= "type=\"$type\"";
            if ($length) {
                if ($type === 'decimal') {
                    list($precision, $scale) = explode(',', $length);
                    $annotation .= ", precision=$precision, scale=$scale";
                } else {
                    $annotation .= ", length=$length";
                }
            }
        } else {
            $annotation .= "type=\"$type\"";
        }
        
        if ($nullable) {
            $annotation .= ", nullable=true";
        }
        
        $annotation .= ")\n     */";
        
        // Property declaration
        $property = "    private " . ($nullable ? "?" : "") . "$typeHint \$$name" . ($nullable ? " = null" : "") . ";";
        
        $propertiesCode[] = "$annotation\n$property\n";
        
        // Getter
        $camelName = ucfirst($name);
        $gettersSettersCode[] = "    public function get$camelName(): " . ($nullable ? "?" : "") . "$typeHint\n" .
                               "    {\n" .
                               "        return \$this->$name;\n" .
                               "    }\n";
        
        // Setter
        $gettersSettersCode[] = "    public function set$camelName(" . ($nullable ? "?" : "") . "$typeHint \$$name): self\n" .
                               "    {\n" .
                               "        \$this->$name = \$$name;\n\n" .
                               "        return \$this;\n" .
                               "    }\n";
    }
    
    // Create entity file
    $entityFile = "$entityDir/$entityName.php";
    $content = "<?php\n\n" .
              "namespace App\\Entity;\n\n" .
              "use App\\Repository\\" . $entityName . "Repository;\n" .
              "use Doctrine\\ORM\\Mapping as ORM;\n\n" .
              "/**\n" .
              " * @ORM\\Entity(repositoryClass=" . $entityName . "Repository::class)\n" .
              ($entityName === 'Table' ? " * @ORM\\Table(name=\"restaurant_table\")\n" : "") .
              " */\n" .
              "class $entityName\n" .
              "{\n" .
              "    /**\n" .
              "     * @ORM\\Id\n" .
              "     * @ORM\\GeneratedValue\n" .
              "     * @ORM\\Column(type=\"integer\")\n" .
              "     */\n" .
              "    private ?int \$id = null;\n\n" .
              implode("\n", $propertiesCode) . "\n" .
              "    public function getId(): ?int\n" .
              "    {\n" .
              "        return \$this->id;\n" .
              "    }\n\n" .
              implode("\n", $gettersSettersCode) .
              "}\n";
    
    file_put_contents($entityFile, $content);
    echo "Created entity: $entityFile\n";
    
    // Create repository file
    $repositoryFile = "$repositoryDir/{$entityName}Repository.php";
    $repoContent = "<?php\n\n" .
                  "namespace App\\Repository;\n\n" .
                  "use App\\Entity\\$entityName;\n" .
                  "use Doctrine\\Bundle\\DoctrineBundle\\Repository\\ServiceEntityRepository;\n" .
                  "use Doctrine\\Persistence\\ManagerRegistry;\n\n" .
                  "/**\n" .
                  " * @extends ServiceEntityRepository<$entityName>\n" .
                  " */\n" .
                  "class {$entityName}Repository extends ServiceEntityRepository\n" .
                  "{\n" .
                  "    public function __construct(ManagerRegistry \$registry)\n" .
                  "    {\n" .
                  "        parent::__construct(\$registry, $entityName::class);\n" .
                  "    }\n\n" .
                  "    public function save($entityName \$entity, bool \$flush = false): void\n" .
                  "    {\n" .
                  "        \$this->getEntityManager()->persist(\$entity);\n\n" .
                  "        if (\$flush) {\n" .
                  "            \$this->getEntityManager()->flush();\n" .
                  "        }\n" .
                  "    }\n\n" .
                  "    public function remove($entityName \$entity, bool \$flush = false): void\n" .
                  "    {\n" .
                  "        \$this->getEntityManager()->remove(\$entity);\n\n" .
                  "        if (\$flush) {\n" .
                  "            \$this->getEntityManager()->flush();\n" .
                  "        }\n" .
                  "    }\n" .
                  "}\n";
    
    file_put_contents($repositoryFile, $repoContent);
    echo "Created repository: $repositoryFile\n";
    
    return true;
}

// Bootstrap the application
$projectDir = dirname(__DIR__);
require_once $projectDir . '/vendor/autoload.php';

echo "======================================================\n";
echo "Quai Antique Restaurant - Database Initialization\n";
echo "======================================================\n";

// Check for force option
$force = in_array('--force', $argv);

// 0. Prepare the database
if ($force) {
    echo "\n[Step 0] Dropping and recreating database\n";
    executeCommand("php $projectDir/bin/console doctrine:database:drop --force --if-exists");
    executeCommand("php $projectDir/bin/console doctrine:database:create --if-not-exists");
} else {
    echo "\n[Step 0] Ensuring database exists\n";
    executeCommand("php $projectDir/bin/console doctrine:database:create --if-not-exists");
}

// 1. Create entity files directly
echo "\n[Step 1] Creating entity classes\n";

// Define all entities with their properties
$entities = [
    'User' => [
        'email' => ['type' => 'string', 'length' => 180],
        'roles' => ['type' => 'json'],
        'password' => ['type' => 'string'],
        'firstName' => ['type' => 'string', 'length' => 64],
        'lastName' => ['type' => 'string', 'length' => 64],
        'createdAt' => ['type' => 'datetime'],
        'updatedAt' => ['type' => 'datetime'],
    ],
    'Category' => [
        'name' => ['type' => 'string', 'length' => 64],
        'description' => ['type' => 'text'],
        'position' => ['type' => 'integer'],
    ],
    'Dish' => [
        'name' => ['type' => 'string', 'length' => 64],
        'description' => ['type' => 'text'],
        'price' => ['type' => 'decimal', 'length' => '10,2'],
        'image' => ['type' => 'string', 'length' => 255, 'nullable' => true],
        'isActive' => ['type' => 'boolean'],
        'createdAt' => ['type' => 'datetime'],
        'updatedAt' => ['type' => 'datetime'],
    ],
    'Allergen' => [
        'name' => ['type' => 'string', 'length' => 64],
        'description' => ['type' => 'text', 'nullable' => true],
    ],
    'Menu' => [
        'title' => ['type' => 'string', 'length' => 64],
        'description' => ['type' => 'text'],
        'price' => ['type' => 'decimal', 'length' => '10,2'],
        'isActive' => ['type' => 'boolean'],
        'startDate' => ['type' => 'datetime', 'nullable' => true],
        'endDate' => ['type' => 'datetime', 'nullable' => true],
    ],
    'Schedule' => [
        'dayOfWeek' => ['type' => 'integer'],
        'lunchOpeningTime' => ['type' => 'datetime', 'nullable' => true],
        'lunchClosingTime' => ['type' => 'datetime', 'nullable' => true],
        'dinnerOpeningTime' => ['type' => 'datetime', 'nullable' => true],
        'dinnerClosingTime' => ['type' => 'datetime', 'nullable' => true],
        'isClosed' => ['type' => 'boolean'],
    ],
    'Reservation' => [
        'date' => ['type' => 'datetime'],
        'guestCount' => ['type' => 'integer'],
        'lastName' => ['type' => 'string', 'length' => 64],
        'firstName' => ['type' => 'string', 'length' => 64, 'nullable' => true],
        'email' => ['type' => 'string', 'length' => 180],
        'phone' => ['type' => 'string', 'length' => 20],
        'status' => ['type' => 'string', 'length' => 20],
        'notes' => ['type' => 'text', 'nullable' => true],
        'allergies' => ['type' => 'text', 'nullable' => true],
        'createdAt' => ['type' => 'datetime'],
    ],
    'Table' => [
        'number' => ['type' => 'integer'],
        'seats' => ['type' => 'integer'],
        'location' => ['type' => 'string', 'length' => 64],
        'isActive' => ['type' => 'boolean'],
    ],
    'Gallery' => [
        'title' => ['type' => 'string', 'length' => 64],
        'filename' => ['type' => 'string', 'length' => 255],
        'description' => ['type' => 'text', 'nullable' => true],
        'isActive' => ['type' => 'boolean'],
        'position' => ['type' => 'integer'],
        'createdAt' => ['type' => 'datetime'],
    ],
];

// Create each entity
foreach ($entities as $entityName => $properties) {
    echo "\nCreating $entityName entity\n";
    createEntityFile($projectDir, $entityName, $properties);
}

// 2. Add entity relationships
echo "\n[Step 2] Adding entity relationships\n";
echo "Please see the Relations.php template file for instructions on adding relationships.\n";

// 3. Create database schema
echo "\n[Step 3] Creating database schema\n";
executeCommand("php $projectDir/bin/console doctrine:schema:create --no-interaction");

// 4. Install fixtures bundle if needed
echo "\n[Step 4] Setting up fixtures\n";
if (!is_dir("$projectDir/src/DataFixtures")) {
    executeCommand("composer require --dev doctrine/doctrine-fixtures-bundle");
}

echo "\nDatabase initialization complete!\n";
echo "Now you should update the entity classes to add relationships.\n";
echo "See the Relations.php file in the src/Entity directory for relationship examples.\n";
