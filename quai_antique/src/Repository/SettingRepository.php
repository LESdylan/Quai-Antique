<?php

namespace App\Repository;

use App\Entity\Setting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Setting>
 *
 * @method Setting|null find($id, $lockMode = null, $lockVersion = null)
 * @method Setting|null findOneBy(array $criteria, array $orderBy = null)
 * @method Setting[]    findAll()
 * @method Setting[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Setting::class);
    }

    /**
     * Find all settings by group
     */
    public function findByGroup(string $group): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.group = :group')
            ->setParameter('group', $group)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Get setting value by name
     */
    public function getValue(string $name, $default = null)
    {
        $setting = $this->findOneBy(['name' => $name]);
        
        return $setting ? $setting->getValue() : $default;
    }
    
    /**
     * Set setting value
     */
    public function setValue(string $name, $value, string $group = 'general'): void
    {
        $entityManager = $this->getEntityManager();
        
        $setting = $this->findOneBy(['name' => $name]);
        
        if (!$setting) {
            $setting = new Setting();
            $setting->setName($name);
            $setting->setGroup($group);
        }
        
        $setting->setValue((string) $value);
        
        $entityManager->persist($setting);
        $entityManager->flush();
    }
    
    /**
     * Get all settings as key-value array
     */
    public function getAllAsArray(): array
    {
        $settings = $this->findAll();
        $result = [];
        
        foreach ($settings as $setting) {
            $result[$setting->getName()] = $setting->getValue();
        }
        
        return $result;
    }
}
