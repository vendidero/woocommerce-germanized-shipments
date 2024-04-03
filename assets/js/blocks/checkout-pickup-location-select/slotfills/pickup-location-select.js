import { ExperimentalOrderShippingPackages } from '@woocommerce/blocks-checkout';
import { registerPlugin } from '@wordpress/plugins';
import { useEffect, useCallback, useState, useMemo, useRef } from "@wordpress/element";
import { useSelect, useDispatch, select, dispatch } from '@wordpress/data';
import { __, _x, sprintf } from '@wordpress/i18n';
import { CART_STORE_KEY, CHECKOUT_STORE_KEY } from '@woocommerce/block-data';

import {
    ValidatedTextInput,
    ValidatedTextInputHandle,
} from '@woocommerce/blocks-checkout';

import { decodeEntities } from '@wordpress/html-entities';
import { getSelectedShippingProviders, Combobox, hasShippingProvider, getCheckoutData, hasPickupLocation } from '@woocommerceGzdShipments/blocks-checkout';

import './style.scss';

const PickupLocationSelect = ({
    extensions,
    cart,
    components
}) => {
    const [ supportsCustomerNumber, setSupportsCustomerNumber ] = useState( false );
    const [ customerNumberIsMandatory, setCustomerNumberIsMandatory ] = useState( false );
    const [ customerNumberFieldLabel, setCustomerNumberFieldLabel ] = useState( _x( 'Customer Number', 'shipments', 'woocommerce-germanized-shipments' ) );

    const {
        shippingRates,
        needsShipping,
        pickupLocations,
        pickupLocationDeliveryAvailable,
        defaultPickupLocation,
        defaultCustomerNumber,
        customerData
    } = useSelect( ( select ) => {
        const isEditor = !! select( 'core/editor' );
        const store = select( CART_STORE_KEY );
        const rates = isEditor
            ? []
            : store.getShippingRates();

        const cartData = store.getCartData();
        const defaultData    = {
            'pickup_location_delivery_available': false,
            'pickup_locations': [],
            'default_pickup_location': '',
            'default_pickup_location_customer_number': '',
        };
        const shipmentsData = cartData.extensions.hasOwnProperty( 'woocommerce-gzd-shipments' ) ? cartData.extensions['woocommerce-gzd-shipments'] : defaultData;

        return {
            shippingRates: rates,
            customerData: store.getCustomerData(),
            needsShipping: store.getNeedsShipping(),
            isLoadingRates: store.isCustomerDataUpdating(),
            isSelectingRate: store.isShippingRateBeingSelected(),
            pickupLocationDeliveryAvailable: shipmentsData['pickup_location_delivery_available'],
            pickupLocations: shipmentsData['pickup_locations'],
            defaultPickupLocation: shipmentsData['default_pickup_location'],
            defaultCustomerNumber: shipmentsData['default_pickup_location_customer_number']
        };
    } );

    const checkoutOptions = getCheckoutData();

    const availableLocations = useMemo(
        () =>
            Object.fromEntries( pickupLocations.map( ( location ) => [ location.code, location ] ) ),
        [ pickupLocations ]
    );

    const getLocationByCode = useCallback( ( code ) => {
        return availableLocations.hasOwnProperty( code ) ? availableLocations[ code ] : null;
    }, [ availableLocations ] );

    useEffect(() => {
        if ( pickupLocationDeliveryAvailable && getLocationByCode( defaultPickupLocation ) ) {
            setOption( 'pickup_location', defaultPickupLocation );
            setOption( 'pickup_location_customer_number', defaultCustomerNumber );
        }
    }, [] );

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

            if ( currentLocation ) {
                setSupportsCustomerNumber( () => { return currentLocation.supports_customer_number } );
                setCustomerNumberIsMandatory( () => { return currentLocation.customer_number_is_mandatory } );
                setCustomerNumberFieldLabel( () => { return currentLocation.customer_number_field_label } );

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

    /**
     * Show a notice in case availability changes or location is not available any longer.
     */
    useEffect(() => {
        const currentLocation = getLocationByCode( checkoutOptions.pickup_location );

        if ( ! pickupLocationDeliveryAvailable || ! currentLocation ) {
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
        pickupLocationDeliveryAvailable,
        locationOptions
    ] );

    if ( needsShipping && pickupLocationDeliveryAvailable ) {
        return (
            <div className="wc-gzd-shipments-pickup-location-delivery">
                <h4>
                    { _x('Not at home? Choose a pickup location', 'shipments', 'woocommerce-germanized-shipments') }
                </h4>
                <Combobox
                    options={ locationOptions }
                    id="pickup-location"
                    key="pickup-location"
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

                { supportsCustomerNumber && (
                    <ValidatedTextInput
                        key="pickup_location_customer_number"
                        value={ checkoutOptions.pickup_location_customer_number }
                        id="pickup-location-customer-number"
                        label={ customerNumberFieldLabel }
                        name="pickup_location_customer_number"
                        required={ customerNumberIsMandatory }
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