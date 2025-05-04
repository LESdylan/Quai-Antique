<?php

namespace App\Controller\Admin;

use App\Entity\Setting;
use App\Repository\SettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/setting')]
class SettingController extends AbstractController
{
    private $entityManager;
    private $settingRepository;

    public function __construct(
        EntityManagerInterface $entityManager,
        SettingRepository $settingRepository
    ) {
        $this->entityManager = $entityManager;
        $this->settingRepository = $settingRepository;
    }

    #[Route('/', name: 'admin_setting_index')]
    public function index(): Response
    {
        $settings = $this->settingRepository->findAll();
        $settingsMap = [];
        
        foreach ($settings as $setting) {
            $settingsMap[$setting->getName()] = $setting->getValue();
        }
        
        return $this->render('admin/setting/index.html.twig', [
            'settings' => $settingsMap,
        ]);
    }

    #[Route('/update', name: 'admin_setting_update', methods: ['POST'])]
    public function update(Request $request): Response
    {
        $data = $request->request->all();
        $group = $request->query->get('group', 'general');
        
        foreach ($data as $key => $value) {
            if (strpos($key, '_') === 0) {
                continue; // Skip CSRF token and other internal fields
            }
            
            $setting = $this->settingRepository->findOneBy(['name' => $key]);
            
            if (!$setting) {
                $setting = new Setting();
                $setting->setName($key);
            }
            
            $setting->setValue($value);
            $this->entityManager->persist($setting);
        }
        
        $this->entityManager->flush();
        $this->addFlash('success', 'Les paramètres ont été mis à jour avec succès.');
        
        return $this->redirectToRoute('admin_setting_index', ['group' => $group]);
    }
}
