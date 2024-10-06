<?php

namespace App\Controller\Admin;

use App\Entity\Currency;
use App\Service\CurrencyService;
use App\Service\FreeCurrencyApiException;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CurrencyCrudController extends AbstractCrudController
{

    public function __construct(private CurrencyService $currencyService, private  ParameterBagInterface $params)
    {
    }

    public static function getEntityFqcn(): string
    {
        return Currency::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->showEntityActionsInlined()
            ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->addBatchAction(Action::new('getCurrent', 'Get current cources')
                ->linkToCrudAction('getCurrentCources')
                ->setIcon('fa fa-solid fa-cloud-arrow-down'))
            ;
    }

    public function getCurrentCources(BatchActionDto $batchActionDto)
    {
        $className = $batchActionDto->getEntityFqcn();
        $entityManager = $this->container->get('doctrine')->getManagerForClass($className);

        foreach ($batchActionDto->getEntityIds() as $id) {
            $currency = $entityManager->find($className, $id);

            try {
                $response = $this->currencyService->latest([
                    'base_currency' => $this->params->get('currency_base'),
                    'currencies'=> $currency->getCode()
                ]);

                if (
                    array_key_exists("data", $response)
                    && array_key_exists($currency->getCode(), $response['data'])
                    && is_numeric($response['data'][$currency->getCode()])
                ) {
                    $currency->setPrice($response['data'][$currency->getCode()]);
                }
            } catch (FreeCurrencyApiException $e) {
                //TODO: тут обработка ошибок
                continue;
            } catch (\Exception $e) {
                //TODO: тут обработка других ошибок
                continue;
            }

        }

        $entityManager->flush();

        return $this->redirect($batchActionDto->getReferrerUrl());
    }

    /*
    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];
    }
    */
}
