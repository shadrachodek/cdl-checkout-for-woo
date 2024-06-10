const settings = window.wc.wcSettings.getSetting( 'cdl_checkout_data', {} );
const label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'CDL Checkout', 'cdl_checkout' );
const Content = () => {
    return window.wp.htmlEntities.decodeEntities( settings.description || '' );
};
// const Icon = () => {
//     return settings.icon
//         ? <img src={settings.icon} style={{ float: 'right', marginRight: '20px' }} />
//         : ''
// };
const Block_Gateway = {
    name: 'cdl_checkout',
    label: label,
    content: Object( window.wp.element.createElement )( Content, null ),
    edit: Object( window.wp.element.createElement )( Content, null ),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway );