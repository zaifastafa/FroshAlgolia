<?php declare(strict_types=1);

use FroshAlgolia\Models\Config;

class Shopware_Controllers_Backend_Algolia extends Shopware_Controllers_Backend_ExtJs
{
    public function preDispatch()
    {
        $this->View()->addTemplateDir($this->container->getParameter('frosh_algolia.view_dir'));
        parent::preDispatch();
    }

    public function getConfigAction()
    {
        $shopId = $this->Request()->getParam('shopId');

        $config = $this->getModelManager()->getRepository(Config::class)->findOneBy(['shop' => $shopId]);

        $this->View()->success = true;

        if ($config) {
            $this->View()->data = $config->getConfig();
        } else {
            $this->View()->data = include $this->container->getParameter('frosh_algolia.plugin_dir') . '/Bootstrap/DefaultConfig.php';
        }
    }

    public function saveConfigAction()
    {
        $shopId = $this->Request()->getParam('shopId');
        $config = $this->getModelManager()->getRepository(Config::class)->findOneBy(['shop' => $shopId]);

        if (!$config) {
            $config = new Config();
            $config->setShop($shopId);
        }

        $config->setConfig(json_decode($this->Request()->getParam('data')));

        $this->getModelManager()->persist($config);
        $this->getModelManager()->flush();

        $this->View()->success = true;
    }
}
