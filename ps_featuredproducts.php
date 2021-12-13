<?php
/**
 * 2007-2020 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;

class Ps_FeaturedProducts extends Module implements WidgetInterface
{
    private $templateFile;

    public function __construct()
    {
        $this->name = 'ps_featuredproducts';
        $this->author = 'PrestaShop';
        $this->version = '2.1.2';
        $this->need_instance = 0;

        $this->ps_versions_compliancy = [
            'min' => '1.7.1.0',
            'max' => _PS_VERSION_,
        ];

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->trans('Featured products', [], 'Modules.Featuredproducts.Admin');
        $this->description = $this->trans('Pick a category and highlight its items, enhance customer experience with a lively homepage.', [], 'Modules.Featuredproducts.Admin');

        $this->templateFile = 'module:ps_featuredproducts/views/templates/hook/ps_featuredproducts.tpl';
    }

    public function install()
    {
        $this->_clearCache('*');

        Configuration::updateValue('HOME_FEATURED_NBR', 8);
        Configuration::updateValue('HOME_FEATURED_CAT', (int) Context::getContext()->shop->getCategory());
        Configuration::updateValue('HOME_FEATURED_RANDOMIZE', false);

        return parent::install()
            && $this->registerHook('actionProductAdd')
            && $this->registerHook('actionProductUpdate')
            && $this->registerHook('actionProductDelete')
            && $this->registerHook('displayHome')
            && $this->registerHook('displayOrderConfirmation2')
            && $this->registerHook('displayCrossSellingShoppingCart')
            && $this->registerHook('actionCategoryUpdate')
            && $this->registerHook('actionAdminGroupsControllerSaveAfter')
        ;
    }

    public function uninstall()
    {
        $this->_clearCache('*');

        return parent::uninstall();
    }

    public function hookActionProductAdd($params)
    {
        $this->_clearCache('*');
    }

    public function hookActionProductUpdate($params)
    {
        $this->_clearCache('*');
    }

    public function hookActionProductDelete($params)
    {
        $this->_clearCache('*');
    }

    public function hookActionCategoryUpdate($params)
    {
        $this->_clearCache('*');
    }

    public function hookActionAdminGroupsControllerSaveAfter($params)
    {
        $this->_clearCache('*');
    }

    public function _clearCache($template, $cache_id = null, $compile_id = null)
    {
        parent::_clearCache($this->templateFile);
    }

    public function getContent()
    {
        $output = '';
        $errors = [];

        if (Tools::isSubmit('submitHomeFeatured')) {
            $nbr = Tools::getValue('HOME_FEATURED_NBR');
            if (!Validate::isInt($nbr) || $nbr <= 0) {
                $errors[] = $this->trans('The number of products is invalid. Please enter a positive number.', [], 'Modules.Featuredproducts.Admin');
            }

            $cat = Tools::getValue('HOME_FEATURED_CAT');
            if (!Validate::isInt($cat) || $cat <= 0) {
                $errors[] = $this->trans('The category ID is invalid. Please choose an existing category ID.', [], 'Modules.Featuredproducts.Admin');
            }

            $rand = Tools::getValue('HOME_FEATURED_RANDOMIZE');
            if (!Validate::isBool($rand)) {
                $errors[] = $this->trans('Invalid value for the "randomize" flag.', [], 'Modules.Featuredproducts.Admin');
            }
            if (count($errors)) {
                $output = $this->displayError(implode('<br />', $errors));
            } else {
                Configuration::updateValue('HOME_FEATURED_NBR', (int) $nbr);
                Configuration::updateValue('HOME_FEATURED_CAT', (int) $cat);
                Configuration::updateValue('HOME_FEATURED_RANDOMIZE', (bool) $rand);

                $this->_clearCache('*');

                $output = $this->displayConfirmation($this->trans('The settings have been updated.', [], 'Admin.Notifications.Success'));
            }
        }

        return $output . $this->renderForm();
    }

    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],

                'description' => $this->trans('To add products to your homepage, simply add them to the corresponding product category (default: "Home").', [], 'Modules.Featuredproducts.Admin'),
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Number of products to be displayed', [], 'Modules.Featuredproducts.Admin'),
                        'name' => 'HOME_FEATURED_NBR',
                        'class' => 'fixed-width-xs',
                        'desc' => $this->trans('Set the number of products that you would like to display on homepage (default: 8).', [], 'Modules.Featuredproducts.Admin'),
                    ],
                    [
                        'type' => 'categories',
                        'tree' => [
                          'id' => 'home_featured_category',
                          'selected_categories' => [Configuration::get('HOME_FEATURED_CAT')],
                        ],
                        'label' => $this->trans('Category from which to pick products to be displayed', [], 'Modules.Featuredproducts.Admin'),
                        'name' => 'HOME_FEATURED_CAT',
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Randomly display featured products', [], 'Modules.Featuredproducts.Admin'),
                        'name' => 'HOME_FEATURED_RANDOMIZE',
                        'class' => 'fixed-width-xs',
                        'desc' => $this->trans('Enable if you wish the products to be displayed randomly (default: no).', [], 'Modules.Featuredproducts.Admin'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => 1,
                                'label' => $this->trans('Yes', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => 0,
                                'label' => $this->trans('No', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];

        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitHomeFeatured';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$fields_form]);
    }

    public function getConfigFieldsValues()
    {
        return [
            'HOME_FEATURED_NBR' => Tools::getValue('HOME_FEATURED_NBR', (int) Configuration::get('HOME_FEATURED_NBR')),
            'HOME_FEATURED_CAT' => Tools::getValue('HOME_FEATURED_CAT', (int) Configuration::get('HOME_FEATURED_CAT')),
            'HOME_FEATURED_RANDOMIZE' => Tools::getValue('HOME_FEATURED_RANDOMIZE', (bool) Configuration::get('HOME_FEATURED_RANDOMIZE')),
        ];
    }

    public function renderWidget($hookName = null, array $configuration = [])
    {
        if (!$this->isCached($this->templateFile, $this->getCacheId('ps_featuredproducts'))) {
            $variables = $this->getWidgetVariables($hookName, $configuration);

            if (empty($variables)) {
                return false;
            }

            $this->smarty->assign($variables);
        }

        return $this->fetch($this->templateFile, $this->getCacheId('ps_featuredproducts'));
    }

    public function getWidgetVariables($hookName = null, array $configuration = [])
    {
        $products = $this->getProducts();

        if (!empty($products)) {
            return [
                'products' => $products,
                'allProductsLink' => Context::getContext()->link->getCategoryLink($this->getConfigFieldsValues()['HOME_FEATURED_CAT']),
            ];
        }

        return false;
    }

    protected function getProducts()
    {
        $category = new Category((int) Configuration::get('HOME_FEATURED_CAT'));

        $searchProvider = new CategoryProductSearchProvider(
            $this->context->getTranslator(),
            $category
        );

        $context = new ProductSearchContext($this->context);

        $query = new ProductSearchQuery();

        $nProducts = Configuration::get('HOME_FEATURED_NBR');
        if ($nProducts < 0) {
            $nProducts = 12;
        }

        $query
            ->setResultsPerPage($nProducts)
            ->setPage(1)
        ;

        if (Configuration::get('HOME_FEATURED_RANDOMIZE')) {
            $query->setSortOrder(SortOrder::random());
        } else {
            $query->setSortOrder(new SortOrder('product', 'position', 'asc'));
        }

        $result = $searchProvider->runQuery(
            $context,
            $query
        );

        $assembler = new ProductAssembler($this->context);

        $presenterFactory = new ProductPresenterFactory($this->context);
        $presentationSettings = $presenterFactory->getPresentationSettings();
        $presenter = $presenterFactory->getPresenter();

        $products_for_template = [];

        foreach ($result->getProducts() as $rawProduct) {
            $products_for_template[] = $presenter->present(
                $presentationSettings,
                $assembler->assembleProduct($rawProduct),
                $this->context->language
            );
        }

        return $products_for_template;
    }

    protected function getCacheId($name = null)
    {
        $cacheId = parent::getCacheId($name);
        if (!empty($this->context->customer->id)) {
            $cacheId .= '|' . $this->context->customer->id;
        }

        return $cacheId;
    }
}
