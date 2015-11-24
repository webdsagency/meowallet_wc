<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings for Meo Wallet Gateway
 */
$data = array(
    'enabled' => array(
        'title' => __('Activar/Desactivar', 'meowallet_wc'),
        'label' => __('Activar o MEO Wallet', 'meowallet_wc'),
        'type' => 'checkbox',
        'description' => __('Activar o MEO Wallet', 'meowallet_wc'),
        'default' => 'yes'
    ),
    'title' => array(
        'title' => __('Titulo', 'meowallet_wc'),
        'type' => 'text',
        'description' => __('Dá um titulo à forma de pagamento para ser visto durante o pagamento', 'meowallet_wc'),
        'default' => __('MEO Wallet', 'meowallet_wc')
    ),
    'description' => array(
        'title' => __('Descrição', 'meowallet_wc'),
        'type' => 'textarea',
        'description' => __('Oferece uma Descrição da forma de pagamento por MEO Wallet aos seus clientes para ser vista durante o processo de pagamento', 'meowallet_wc'),
        'default' => __('Pagar com MEO Wallet - MEO Wallet, Multibanco, Cartão de Crédito/Débito', 'meowallet_wc')
    ),
    'apikey_live' => array(
        'title' => __('Chave API', 'meowallet_wc'),
        'type' => 'text',
        'description' => __('Introduza a sua Chave API do MEO Wallet . Não é a mesma que a Chave API do MEO Wallet-Sandbox. <br />Para obter a sua Chave API, clique <a target="_blank" href="https://www.wallet.pt/login/">aqui</a>', 'meowallet_wc'),
        'default' => '',
        'class' => 'production_settings sensitive'
    ),
    'apikey_sandbox' => array(
        'title' => __('Chave API Sandbox', 'meowallet_wc'),
        'type' => 'text',
        'description' => __('Introduza a sua Chave API de testes do MEO Wallet. <br />Para obter a sua Chave API, clique <a target="_blank" href="https://www.sandbox.meowallet.pt/login/">aqui</a>', 'meowallet_wc'),
        'default' => '',
        'class' => 'sandbox_settings sensitive'
    ),
    'environment' => array(
        'title' => __('Escolher Ambiente de Trabalho', 'meowallet_wc'),
        'type' => 'select',
        'label' => __('Activar o MEO Wallet em modo de tests!', 'meowallet_wc'),
        'description' => __('Escolha o seu Ambiente de Trabalho entre Teste e Produção.', 'meowallet_wc'),
        'default' => 'sandbox',
        'options' => array(
            'sandbox' => __('Teste', 'meowallet_wc'),
            'production' => __('Produção', 'meowallet_wc'),
        ),
    ),
    'debug' => array(
        'title' => __('Debug Log', 'woocommerce'),
        'type' => 'checkbox',
        'label' => __('Enable logging', 'woocommerce'),
        'default' => 'no',
        'description' => sprintf(__('Log Meo Wallet events, inside <code>%s</code>', 'woocommerce'), wc_get_log_file_path('meowallet_wc'))
    ),
);

if (get_woocommerce_currency() != 'EUR') {
    $data['ex_to_euro'] = array(
        'title' => __("Taxa de Cambio para Euro", 'meowallet_wc'),
        'type' => 'text',
        'description' => 'Taxa de Cambio para Euro',
        'default' => '1',
    );
}
return $data;
