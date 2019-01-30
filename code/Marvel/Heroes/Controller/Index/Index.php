<?php
namespace Marvel\Heroes\Controller\Index;

use Magento\Framework\App\Action\Action;

class Index extends Action

{

    public function __construct(
        \Magento\Framework\App\Action\Context $context)
          {
            return parent::__construct($context);
          }
 
  public function execute()
  {
       
    header('Content-type: application/json');
    $jsonData = json_decode(file_get_contents('php://input'),true);

    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
    $objectManager->get('Marvel\Heroes\Helpers\Data')->createMageOrder($jsonData);

    exit;
  }
}