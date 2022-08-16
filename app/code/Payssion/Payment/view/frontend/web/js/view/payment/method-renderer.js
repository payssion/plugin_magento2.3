define(
    [
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function ($,
              Component,
              rendererList) {
        'use strict';

        var defaultComponent = 'Payssion_Payment/js/view/payment/method-renderer/default';

        var methods = [
            {type: 'payssion_payment_affinepg_my', component: defaultComponent},
            {type: 'payssion_payment_alfaclick_ru', component: defaultComponent},
            {type: 'payssion_payment_alfamart_id', component: defaultComponent},
            {type: 'payssion_payment_alipay_cn', component: defaultComponent},
            {type: 'payssion_payment_amb_my', component: defaultComponent},
            {type: 'payssion_payment_aptg_tw', component: defaultComponent},
            {type: 'payssion_payment_atmva_id', component: defaultComponent},
            {type: 'payssion_payment_banamex_mx', component: defaultComponent},
            {type: 'payssion_payment_bankcard_ru', component: defaultComponent},
            {type: 'payssion_payment_bancochile_cl', component: defaultComponent},
            {type: 'payssion_payment_bancomer_mx', component: defaultComponent},
            {type: 'payssion_payment_bancontact_be', component: defaultComponent},
            {type: 'payssion_payment_bitcash_jp', component: defaultComponent},
            {type: 'payssion_payment_bitcoin', component: defaultComponent},
            {type: 'payssion_payment_boacompra', component: defaultComponent},
            {type: 'payssion_payment_boleto_br', component: defaultComponent},
            {type: 'payssion_payment_boost_my', component: defaultComponent},
            {type: 'payssion_payment_caixa_br', component: defaultComponent},
            {type: 'payssion_payment_cashu', component: defaultComponent},
            {type: 'payssion_payment_cherrycredits', component: defaultComponent},
            {type: 'payssion_payment_cht839_tw', component: defaultComponent},
            {type: 'payssion_payment_cimb_my', component: defaultComponent},
            {type: 'payssion_payment_creditcard_kr', component: defaultComponent},
            {type: 'payssion_payment_creditcard_jp', component: defaultComponent},
            {type: 'payssion_payment_creditcard_mx', component: defaultComponent},
            {type: 'payssion_payment_davivienda_co', component: defaultComponent},
            {type: 'payssion_payment_docomo_jp', component: defaultComponent},
            {type: 'payssion_payment_doku_id', component: defaultComponent},
            {type: 'payssion_payment_dotpay_pl', component: defaultComponent},
            {type: 'payssion_payment_dragonpay_ph', component: defaultComponent},
            {type: 'payssion_payment_ebanking_kr', component: defaultComponent},
            {type: 'payssion_payment_efecty_co', component: defaultComponent},
            {type: 'payssion_payment_enets_sg', component: defaultComponent},
            {type: 'payssion_payment_eps_at', component: defaultComponent},
            {type: 'payssion_payment_fetnet_tw', component: defaultComponent},
            {type: 'payssion_payment_fpx_my', component: defaultComponent},
            {type: 'payssion_payment_gash_tw', component: defaultComponent},
            {type: 'payssion_payment_gcash_ph', component: defaultComponent},
            {type: 'payssion_payment_giropay_de', component: defaultComponent},
            {type: 'payssion_payment_grabpay_my', component: defaultComponent},
            {type: 'payssion_payment_hlb_my', component: defaultComponent},
            {type: 'payssion_payment_ideal_nl', component: defaultComponent},
            {type: 'payssion_payment_indosat_id', component: defaultComponent},
            {type: 'payssion_payment_itau_br', component: defaultComponent},
            {type: 'payssion_payment_kakaopay_kr', component: defaultComponent},
            {type: 'payssion_payment_klarna', component: defaultComponent},
            {type: 'payssion_payment_m1_sg', component: defaultComponent},
            {type: 'payssion_payment_maybank2u_my', component: defaultComponent},
            {type: 'payssion_payment_molpay', component: defaultComponent},
            {type: 'payssion_payment_molpoints', component: defaultComponent},
            {type: 'payssion_payment_multibanco_pt', component: defaultComponent},
            {type: 'payssion_payment_mybank', component: defaultComponent},
            {type: 'payssion_payment_neosurf', component: defaultComponent},
            {type: 'payssion_payment_netcash_jp', component: defaultComponent},
            {type: 'payssion_payment_onecard', component: defaultComponent},
            {type: 'payssion_payment_otc_th', component: defaultComponent},
            {type: 'payssion_payment_oxxo_mx', component: defaultComponent},
            {type: 'payssion_payment_p24_pl', component: defaultComponent},
            {type: 'payssion_payment_pagofacil_ar', component: defaultComponent},
            {type: 'payssion_payment_paybybankapp_gb', component: defaultComponent},
            {type: 'payssion_payment_paysafecard', component: defaultComponent},
            {type: 'payssion_payment_paysbuy_th', component: defaultComponent},
            {type: 'payssion_payment_poli_au', component: defaultComponent},
            {type: 'payssion_payment_poli_nz', component: defaultComponent},
            {type: 'payssion_payment_pse_co', component: defaultComponent},
            {type: 'payssion_payment_qiwi', component: defaultComponent},
            {type: 'payssion_payment_rapipago_ar', component: defaultComponent},
            {type: 'payssion_payment_redcompra_cl', component: defaultComponent},
            {type: 'payssion_payment_redpagos_uy', component: defaultComponent},
            {type: 'payssion_payment_rhb_my', component: defaultComponent},
            {type: 'payssion_payment_santander_br', component: defaultComponent},
            {type: 'payssion_payment_sberbank_ru', component: defaultComponent},
            {type: 'payssion_payment_singpost_sg', component: defaultComponent},
            {type: 'payssion_payment_singtel_sg', component: defaultComponent},
            {type: 'payssion_payment_smartsun_ph', component: defaultComponent},
            {type: 'payssion_payment_sofort', component: defaultComponent},
            {type: 'payssion_payment_spei_mx', component: defaultComponent},
            {type: 'payssion_payment_starhub_sg', component: defaultComponent},
            {type: 'payssion_payment_telcovoucher_vn', component: defaultComponent},
            {type: 'payssion_payment_tenpay_cn', component: defaultComponent},
            {type: 'payssion_payment_touchngo_my', component: defaultComponent},
            {type: 'payssion_payment_truemoney_th', component: defaultComponent},
            {type: 'payssion_payment_trustpay', component: defaultComponent},
            {type: 'payssion_payment_tm_tw', component: defaultComponent},
            {type: 'payssion_payment_unionpay_cn', component: defaultComponent},
            {type: 'payssion_payment_verkkopankki_fi', component: defaultComponent},
            {type: 'payssion_payment_vtcpay_vn', component: defaultComponent},
            {type: 'payssion_payment_webmoney', component: defaultComponent},
            {type: 'payssion_payment_webpay_cl', component: defaultComponent},
            {type: 'payssion_payment_xl_id', component: defaultComponent},
            {type: 'payssion_payment_yamoney', component: defaultComponent}
        ];
        $.each(methods, function (k, method) {
            rendererList.push(method);
        });

        return Component.extend({});
    }
);