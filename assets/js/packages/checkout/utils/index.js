export const getSelectedShippingProviders = (
    shippingRates
) => {
    return Object.fromEntries( shippingRates.map( ( { package_id: packageId, shipping_rates: packageRates } ) => {
        const meta_data = packageRates.find( ( rate ) => rate.selected )?.meta_data || [];
        let provider = '';

        meta_data.map( ( metaField ) => {
            if ( 'shipping_provider' === metaField.key || '_shipping_provider' === metaField.key ) {
                provider = metaField.value;
            }
        } );

        return [
            packageId,
            provider
        ];
    } ) );
};

export const hasShippingProvider = ( shippingProviders, shippingProvider ) => {
    return Object.values( shippingProviders ).includes( shippingProvider );
};