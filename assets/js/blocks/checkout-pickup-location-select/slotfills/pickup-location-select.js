import { ExperimentalOrderShippingPackages } from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect, useCallback, useState, useMemo } from "@wordpress/element";
import { useSelect, useDispatch, select, dispatch } from '@wordpress/data';
import triggerFetch from '@wordpress/api-fetch';
import { extensionCartUpdate } from '@woocommerce/blocks-checkout';
import classnames from 'classnames';
import { getSetting } from '@woocommerce/settings';
import { __, _x, sprintf } from '@wordpress/i18n';
import { SVG } from '@wordpress/components';
import _ from 'lodash';
import { CART_STORE_KEY, CHECKOUT_STORE_KEY, PAYMENT_STORE_KEY, VALIDATION_STORE_KEY } from '@woocommerce/block-data';

import {
    ValidatedTextInput,
    ValidatedTextInputHandle,
} from '@woocommerce/blocks-checkout';

import { decodeEntities } from '@wordpress/html-entities';
import { useDebouncedCallback, useDebounce } from 'use-debounce';
import { getSelectedShippingProviders, Combobox, hasShippingProvider } from '@woocommerceGzdShipments/blocks-checkout';

const PickupLocationSelect = ({
    extensions,
    cart,
    components
}) => {
    const [ needsCustomerNumber, setNeedsCustomerNumber ] = useState( false );

    const {
        shippingRates,
        needsShipping,
        pickupLocations,
        pickupLocationDeliveryAvailable,
        customerData
    } = useSelect( ( select ) => {
        const isEditor = !! select( 'core/editor' );
        const store = select( CART_STORE_KEY );
        const rates = isEditor
            ? []
            : store.getShippingRates();

        const cartData = store.getCartData();
        const shipmentsData = cartData.extensions.hasOwnProperty( 'woocommerce-gzd-shipments' ) ? cartData.extensions['woocommerce-gzd-shipments'] : { 'pickup_location_delivery_available': false, 'pickup_locations': [] };

        return {
            shippingRates: rates,
            customerData: store.getCustomerData(),
            needsShipping: store.getNeedsShipping(),
            isLoadingRates: store.isCustomerDataUpdating(),
            isSelectingRate: store.isShippingRateBeingSelected(),
            pickupLocationDeliveryAvailable: shipmentsData['pickup_location_delivery_available'],
            pickupLocations: shipmentsData['pickup_locations']
        };
    } );

    const { checkoutOptions } = useSelect( ( select ) => {
        const store = select( CHECKOUT_STORE_KEY );

        const extensionsData = store.getExtensionData();
        const shipmentsData = extensionsData.hasOwnProperty( 'woocommerce-gzd-shipments' ) ? extensionsData['woocommerce-gzd-shipments'] : { 'pickup_location': '', 'pickup_location_customer_number': '' };

        return {
            checkoutOptions: shipmentsData
        };
    } );

    const availableLocations = useMemo(
        () =>
            Object.fromEntries( pickupLocations.map( ( location ) => [ location.code, location ] ) ),
        [ pickupLocations ]
    );

    const getLocationByCode = useCallback( ( code ) => {
        return availableLocations.hasOwnProperty( code ) ? availableLocations[ code ] : null;
    }, [ availableLocations ] );

    const setOption = useCallback( ( option, value ) => {
        checkoutOptions[ option ] = value;

        dispatch( CHECKOUT_STORE_KEY ).__internalSetExtensionData( 'woocommerce-gzd-shipments', checkoutOptions );
    }, [ checkoutOptions ] );

    const locationOptions = useMemo(
        () =>
            pickupLocations.map(
                ( location ) => ( {
                    value: location.code,
                    label: decodeEntities( location.formatted_address ),
                } )
            ),
        [ pickupLocations ]
    );

    const shippingAddress = customerData.shippingAddress;
    const { setShippingAddress, setBillingAddress } = useDispatch( CART_STORE_KEY );

    useEffect(() => {
        if ( checkoutOptions.pickup_location ) {
            const currentLocation = getLocationByCode( checkoutOptions.pickup_location );

            if ( ! currentLocation ) {
                setOption( 'pickup_location', '' );
                setOption( 'pickup_location_customer_number', '' );
            }
        }
    }, [
        locationOptions
    ] );

    useEffect(() => {
        if ( checkoutOptions.pickup_location ) {
            const currentLocation = getLocationByCode( checkoutOptions.pickup_location );

            if ( currentLocation ) {
                setNeedsCustomerNumber( () => { return currentLocation.needs_customer_number } );

                const newShippingAddress = { ...shippingAddress };

                Object.keys( currentLocation.address_replacements ).forEach( addressField => {
                    const value = currentLocation.address_replacements[ addressField ];

                    if ( value ) {
                        newShippingAddress[ addressField ] = value;
                    }
                });

                if ( newShippingAddress !== shippingAddress ) {
                    setShippingAddress( newShippingAddress );
                }
            }
        }
    }, [
        checkoutOptions.pickup_location
    ] );

    useEffect(() => {
        if ( ! pickupLocationDeliveryAvailable ) {
            let showNotice = checkoutOptions.pickup_location ? true : false;

            setOption( 'pickup_location', '' );
            setOption( 'pickup_location_customer_number', '' );

            if ( showNotice ) {
                dispatch( 'core/notices' ).createNotice(
                    'warning',
                    _x( 'Your pickup location chosen is not available any longer. Please review your shipping address.', 'shipments', 'woocommerce-germanized-shipments' ),
                    {
                        id: 'wc-gzd-shipments-pickup-location-missing',
                        context: 'wc/checkout/shipping-address',
                    }
                );
            }
        }
    }, [
        pickupLocationDeliveryAvailable
    ] );

    console.log(checkoutOptions);

    if ( needsShipping && pickupLocationDeliveryAvailable ) {
        return (
            <div className="wc-gzd-shipments-pickup-location-delivery">
                <h4>
                    { _x('Not at home? Choose a pickup location', 'shipments', 'woocommerce-germanized-shipments') }
                </h4>
                <Combobox
                    options={ locationOptions }
                    id="pickup-location"
                    name="pickup_location"
                    label={ _x( 'Pickup location', 'shipments', 'woocommerce-germanized-shipments' ) }
                    errorId="pickup-location"
                    value={ checkoutOptions.pickup_location }
                    required={ false }
                    onChange={ ( newLocationCode ) => {
                        if ( availableLocations.hasOwnProperty( newLocationCode ) ) {
                            setOption( 'pickup_location', newLocationCode );
                        } else {
                            setOption( 'pickup_location', '' );
                        }
                    } }
                />

                { needsCustomerNumber && (
                    <ValidatedTextInput
                        key="pickup_location_customer_number"
                        value={ checkoutOptions.pickup_location_customer_number }
                        id="pickup-location-customer-number"
                        label={ _x( "Customer Number", 'shipments', 'woocommerce-germanized-shipments' ) }
                        name="pickup_location_customer_number"
                        required={ true }
                        maxLength="20"
                        onChange={ ( newValue ) => {
                            setOption( 'pickup_location_customer_number', newValue );
                        } }
                    />
                ) }
            </div>
        );
    }

    return null;
};

const render = () => {
    return (
        <ExperimentalOrderShippingPackages>
            <PickupLocationSelect/>
        </ExperimentalOrderShippingPackages>
    );
};

registerPlugin('woocommerce-gzd-shipments-pickup-location-select', {
    render,
    scope: 'woocommerce-checkout',
});