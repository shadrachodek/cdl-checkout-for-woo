import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

const { registerPaymentMethod } = window.wc.wcBlocksRegistry
const { getSetting } = window.wc.wcSettings

const settings = getSetting( 'cdl_checkout_data', {} )


const defaultLabel = __("CDL Checkout ", "cdl-checkout");

const label =  decodeEntities(settings.title) || defaultLabel;

const Content = () => {
    return decodeEntities( settings.description || '' )
}


const Logo = ({ url, label }) => (
    <div style={{ display: "flex", flexDirection: "row", gap: "0.5rem", flexWrap: "wrap" }}>
        <img src={url} alt={label} style={{ width: '75px', height: 'auto' }} />
    </div>
);

registerPaymentMethod( {
    name: "cdl_checkout",
    label: (
        <>
            <div style={{ display: "flex", flexDirection: "row", gap: "0.5rem" }}>
                <div>{label}</div>
                <Logo url={settings.logo_url} label={label} />
            </div>
        </>
    ),
    content: <Content />,
    edit: <Content />,
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    }
} )