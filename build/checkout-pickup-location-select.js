!function(){var e,t={387:function(e,t,n){"use strict";n.r(t);var c=window.React,o=window.wc.blocksCheckout,r=window.wp.plugins,i=window.wp.element,a=window.wp.data,s=window.wp.i18n,u=window.wp.apiFetch,l=n.n(u),p=window.wc.wcBlocksData,d=n(184),m=n.n(d),h=window.wp.htmlEntities,f=window.wcGzdShipments.blocksCheckout;const k=({currentPickupLocation:e,shippingAddress:t})=>{(0,i.useEffect)((()=>{let t=null,c=null;if(null!==n.current){const{ownerDocument:e}=n.current,{defaultView:o}=e;c=o.document.getElementsByClassName("wp-block-woocommerce-checkout-shipping-address-block")[0],c&&(t=c.getElementsByClassName("wc-block-components-address-form")[0],t||(t=c.getElementsByClassName("wc-block-components-address-form-wrapper")[0]))}if(e){if(c&&!c.getElementsByClassName("managed-by-pickup-location-notice")[0]){const e=c.getElementsByClassName("wc-block-components-title")[0];e&&(e.innerHTML+='<span class="managed-by-pickup-location-notice">'+(0,s._x)('Managed by&nbsp;<a href="#current-pickup-location">pickup location</a>',"shipments","woocommerce-germanized-shipments")+"</span>")}Object.keys(e.address_replacements).forEach((n=>{if(e.address_replacements[n]&&t){const e=t.getElementsByClassName("wc-block-components-address-form__"+n)[0];if(e){e.classList.add("managed-by-pickup-location");let t=e.getElementsByTagName("input");t.length>0&&(t[0].readOnly=!0)}}}))}else{if(c){const e=c.getElementsByClassName("managed-by-pickup-location-notice")[0];e&&e.remove()}if(t){const e=t.getElementsByTagName("div");for(let t=0;t<e.length;t++){const n=e[t];if(Array.from(n.classList).includes("managed-by-pickup-location")){n.classList.remove("managed-by-pickup-location");let e=n.getElementsByTagName("input");e.length>0&&(e[0].readOnly=!1)}}}}}),[e,t]);const n=(0,i.useRef)(null);return(0,c.createElement)("div",{ref:n})},g=({currentPickupLocation:e,onRemovePickupLocation:t})=>(0,c.createElement)("h4",{className:"current-pickup-location",id:"current-pickup-location"},(0,c.createElement)("span",{className:"currently-shipping-to-title"},(0,s.sprintf)((0,s._x)("Currently shipping to: %s","shipments","woocommerce-germanized-shipments"),e.label)),(0,c.createElement)("a",{className:"pickup-location-remove",href:"#",onClick:e=>{e.preventDefault(),t()}},(0,c.createElement)("svg",{width:"24",height:"24",viewBox:"0 0 24 24",fill:"none",xmlns:"http://www.w3.org/2000/svg"},(0,c.createElement)("path",{d:"M12 13.06l3.712 3.713 1.061-1.06L13.061 12l3.712-3.712-1.06-1.06L12 10.938 8.288 7.227l-1.061 1.06L10.939 12l-3.712 3.712 1.06 1.061L12 13.061z",fill:"currentColor"})))),_=({isAvailable:e,pickupLocationOptions:t,currentPickupLocation:n,getPickupLocationByCode:r,onChangePickupLocation:i,onRemovePickupLocation:a,onChangePickupLocationCustomerNumber:u,currentPickupLocationCustomerNumber:l,isSearching:p,pickupLocationSearchAddress:d,onChangePickupLocationSearch:h})=>{const k=t.length>0;return e?(0,c.createElement)("div",{className:"wc-gzd-shipments-pickup-location-delivery"},!n&&(0,c.createElement)("h4",null,(0,c.createElement)("span",{className:"pickup-location-notice-title"},(0,s._x)("Not at home? Choose a pickup location","shipments","woocommerce-germanized-shipments"))),n&&(0,c.createElement)(g,{currentPickupLocation:n,onRemovePickupLocation:a}),(0,c.createElement)("div",{className:"pickup-location-search-fields"},(0,c.createElement)(o.ValidatedTextInput,{key:"pickup_location_search_address",value:d.address_1?d.address_1:"",id:"pickup-location-search-address",label:(0,s._x)("Address","shipments","woocommerce-germanized-shipments"),name:"pickup_location_search_address",onChange:e=>{h({address_1:e})}}),(0,c.createElement)(o.ValidatedTextInput,{key:"pickup_location_search_postcode",value:d.postcode?d.postcode:"",id:"pickup-location-search-postcode",label:(0,s._x)("Postcode","shipments","woocommerce-germanized-shipments"),name:"pickup_location_search_postcode",onChange:e=>{h({postcode:e})}})),(0,c.createElement)("div",{className:m()("pickup-location-search-results",{"is-searching":p})},p&&(0,c.createElement)(f.Spinner,null),k&&(0,c.createElement)(f.Combobox,{options:t,id:"pickup-location-search",key:"pickup-location-search",name:"pickup_location-search",label:(0,s._x)("Choose a pickup location","shipments","woocommerce-germanized-shipments"),errorId:"pickup-location-search",allowReset:!!n,value:n?n.code:"",onChange:e=>{i(e)},required:!1}),!k&&(0,c.createElement)("p",null,(0,s._x)("Sorry, we did not find any pickup locations nearby.","shipments","woocommerce-germanized-shipments"))),n&&n.supports_customer_number&&(0,c.createElement)(o.ValidatedTextInput,{key:"pickup_location_customer_number",value:l,id:"pickup-location-customer-number",label:n.customer_number_field_label,name:"pickup_location_customer_number",required:n.customer_number_is_mandatory,maxLength:"20",onChange:u})):null};(0,r.registerPlugin)("woocommerce-gzd-shipments-pickup-location-select",{render:()=>{const[e,t]=(0,i.useState)(null),[n,r]=(0,i.useState)(!1),[u,d]=(0,i.useState)(!1),[m,g]=(0,i.useState)(""),[w,b]=(0,i.useState)(null),[v,y]=(0,i.useState)({postcode:null,address_1:null}),{shippingRates:E,cartDataLoaded:C,needsShipping:S,defaultPickupLocations:L,pickupLocationDeliveryAvailable:P,defaultPickupLocation:x,defaultCustomerNumber:O,customerData:N}=(0,a.useSelect)((e=>{const t=!!e("core/editor"),n=e(p.CART_STORE_KEY),c=t?[]:n.getShippingRates(),o=n.getCartData(),r=o.extensions.hasOwnProperty("woocommerce-gzd-shipments")?o.extensions["woocommerce-gzd-shipments"]:{pickup_location_delivery_available:!1,pickup_locations:[],default_pickup_location:"",default_pickup_location_customer_number:""};return{shippingRates:c,cartDataLoaded:n.hasFinishedResolution("getCartData"),customerData:n.getCustomerData(),needsShipping:n.getNeedsShipping(),isLoadingRates:n.isCustomerDataUpdating(),isSelectingRate:n.isShippingRateBeingSelected(),pickupLocationDeliveryAvailable:r.pickup_location_delivery_available,defaultPickupLocations:r.pickup_locations,defaultPickupLocation:r.default_pickup_location,defaultCustomerNumber:r.default_pickup_location_customer_number}})),R=N.shippingAddress,{setShippingAddress:z,updateCustomerData:D}=(0,a.useDispatch)(p.CART_STORE_KEY),A=(0,f.getCheckoutData)(),T=P&&S,B=(0,i.useMemo)((()=>{let t=null==w?L:w;return e&&t.push(e),t}),[w,L,e]),j=(0,i.useMemo)((()=>Object.fromEntries(B.map((e=>[e.code,e])))),[B]),M=(0,i.useCallback)((e=>j.hasOwnProperty(e)?j[e]:null),[j]),F=(0,i.useMemo)((()=>{const e=[];let t=[];for(const n of B)e.includes(n.code)||t.push({value:n.code,label:(0,h.decodeEntities)(n.formatted_address)}),e.push(n.code);return t}),[B]),I=(0,i.useMemo)((()=>{let t={address_1:R.address_1,postcode:R.postcode};return e&&t.address_1===e.label&&(t.address_1=""),null!=v.address_1&&(t.address_1=v.address_1),null!=v.postcode&&(t.postcode=v.postcode),t}),[R,v,e]),K=(0,i.useCallback)(((e,t)=>{A[e]=t,A.pickup_location||(A.pickup_location_customer_number=""),(0,a.dispatch)(p.CHECKOUT_STORE_KEY).__internalSetExtensionData("woocommerce-gzd-shipments",A)}),[A]);(0,i.useEffect)((()=>{P&&M(x)&&(K("pickup_location",x),K("pickup_location_customer_number",O))}),[x]),(0,i.useEffect)((()=>{if(r((()=>!0)),A.pickup_location){const e=M(A.pickup_location);if(e){t((()=>e));const n={...R};Object.keys(e.address_replacements).forEach((t=>{const c=e.address_replacements[t];c&&(n[t]=c)})),n!==R&&(z(R),D({shipping_address:n},!1))}else t((()=>null))}else t((()=>null))}),[A.pickup_location]),(0,i.useEffect)((()=>{const e=M(A.pickup_location);P&&e||A.pickup_location&&(K("pickup_location",""),(0,a.dispatch)("core/notices").createNotice("warning",(0,s._x)("Your pickup location chosen is not available any longer. Please review your shipping address.","shipments","woocommerce-germanized-shipments"),{id:"wc-gzd-shipments-pickup-location-missing",context:"wc/checkout/shipping-address"}))}),[P]);const V=(0,i.useCallback)((e=>{const t={address:e,provider:m};l()({path:"/wc/store/v1/cart/search-pickup-locations",method:"POST",data:t,cache:"no-store",parse:!1}).then((e=>{l().setNonce(e.headers),e.json().then((function(e){b(e.pickup_locations),d(!1)}))})).catch((e=>{}))}),[m,b,d]),W=function(e,t,n){var o=this,r=(0,c.useRef)(null),i=(0,c.useRef)(0),a=(0,c.useRef)(null),s=(0,c.useRef)([]),u=(0,c.useRef)(),l=(0,c.useRef)(),p=(0,c.useRef)(e),d=(0,c.useRef)(!0);(0,c.useEffect)((function(){p.current=e}),[e]);var m=!t&&0!==t&&"undefined"!=typeof window;if("function"!=typeof e)throw new TypeError("Expected a function");t=+t||0;var h=!!(n=n||{}).leading,f=!("trailing"in n)||!!n.trailing,k="maxWait"in n,g=k?Math.max(+n.maxWait||0,t):null;(0,c.useEffect)((function(){return d.current=!0,function(){d.current=!1}}),[]);var _=(0,c.useMemo)((function(){var e=function(e){var t=s.current,n=u.current;return s.current=u.current=null,i.current=e,l.current=p.current.apply(n,t)},n=function(e,t){m&&cancelAnimationFrame(a.current),a.current=m?requestAnimationFrame(e):setTimeout(e,t)},c=function(e){if(!d.current)return!1;var n=e-r.current;return!r.current||n>=t||n<0||k&&e-i.current>=g},_=function(t){return a.current=null,f&&s.current?e(t):(s.current=u.current=null,l.current)},w=function e(){var o=Date.now();if(c(o))return _(o);if(d.current){var a=t-(o-r.current),s=k?Math.min(a,g-(o-i.current)):a;n(e,s)}},b=function(){var p=Date.now(),m=c(p);if(s.current=[].slice.call(arguments),u.current=o,r.current=p,m){if(!a.current&&d.current)return i.current=r.current,n(w,t),h?e(r.current):l.current;if(k)return n(w,t),e(r.current)}return a.current||n(w,t),l.current};return b.cancel=function(){a.current&&(m?cancelAnimationFrame(a.current):clearTimeout(a.current)),i.current=0,s.current=r.current=u.current=a.current=null},b.isPending=function(){return!!a.current},b.flush=function(){return a.current?_(Date.now()):l.current},b}),[h,k,t,g,f,m]);return _}((e=>{V(e)}),1e3),Y=(0,i.useCallback)((e=>{y((t=>{let n={...t,...e};return null==n.address_1&&(n.address_1=R.address_1),null==n.postcode&&(n.postcode=R.postcode),n}))}),[y,v,R]);(0,i.useEffect)((()=>{if(T&&v.postcode){d(!0);const e={...v,country:R.country};W(e)}}),[T,v,d]);const q=(0,i.useCallback)((()=>{K("pickup_location",""),(0,a.dispatch)("core/notices").createNotice("warning",(0,s._x)("Please review your shipping address.","shipments","woocommerce-germanized-shipments"),{id:"wc-gzd-shipments-review-shipping-address",context:"wc/checkout/shipping-address"})}),[j,R,A]),G=(0,i.useCallback)((e=>{if(j.hasOwnProperty(e)){K("pickup_location",e),y({address_1:""});const{removeNotice:t}=(0,a.dispatch)("core/notices");t("wc-gzd-shipments-review-shipping-address","wc/checkout/shipping-address"),t("wc-gzd-shipments-pickup-location-missing","wc/checkout/shipping-address")}else e?K("pickup_location",""):q()}),[j,y,R,A]),H=(0,i.useCallback)((e=>{K("pickup_location_customer_number",e)}),[A]);return(0,i.useEffect)((()=>{const t=(0,f.getSelectedShippingProviders)(E),n=Object.keys(t).length>0?t[0]:"";n!==m&&g((t=>(""!==t&&n!==t&&(b(null),e&&q()),n)))}),[E]),(0,i.useRef)(null),(0,c.createElement)(o.ExperimentalOrderShippingPackages,null,(0,c.createElement)(_,{pickupLocationOptions:F,getPickupLocationByCode:M,isAvailable:T,isSearching:u,onRemovePickupLocation:q,currentPickupLocation:e,onChangePickupLocation:G,onChangePickupLocationSearch:Y,pickupLocationSearchAddress:I,onChangePickupLocationCustomerNumber:H,currentPickupLocationCustomerNumber:e?A.pickup_location_customer_number:""}),(0,c.createElement)(k,{currentPickupLocation:e,shippingAddress:R}))},scope:"woocommerce-checkout"})},184:function(e,t){var n;!function(){"use strict";var c={}.hasOwnProperty;function o(){for(var e=[],t=0;t<arguments.length;t++){var n=arguments[t];if(n){var r=typeof n;if("string"===r||"number"===r)e.push(n);else if(Array.isArray(n)){if(n.length){var i=o.apply(null,n);i&&e.push(i)}}else if("object"===r)if(n.toString===Object.prototype.toString)for(var a in n)c.call(n,a)&&n[a]&&e.push(a);else e.push(n.toString())}}return e.join(" ")}e.exports?(o.default=o,e.exports=o):void 0===(n=function(){return o}.apply(t,[]))||(e.exports=n)}()}},n={};function c(e){var o=n[e];if(void 0!==o)return o.exports;var r=n[e]={exports:{}};return t[e](r,r.exports,c),r.exports}c.m=t,e=[],c.O=function(t,n,o,r){if(!n){var i=1/0;for(l=0;l<e.length;l++){n=e[l][0],o=e[l][1],r=e[l][2];for(var a=!0,s=0;s<n.length;s++)(!1&r||i>=r)&&Object.keys(c.O).every((function(e){return c.O[e](n[s])}))?n.splice(s--,1):(a=!1,r<i&&(i=r));if(a){e.splice(l--,1);var u=o();void 0!==u&&(t=u)}}return t}r=r||0;for(var l=e.length;l>0&&e[l-1][2]>r;l--)e[l]=e[l-1];e[l]=[n,o,r]},c.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return c.d(t,{a:t}),t},c.d=function(e,t){for(var n in t)c.o(t,n)&&!c.o(e,n)&&Object.defineProperty(e,n,{enumerable:!0,get:t[n]})},c.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},c.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},function(){var e={157:0,352:0};c.O.j=function(t){return 0===e[t]};var t=function(t,n){var o,r,i=n[0],a=n[1],s=n[2],u=0;if(i.some((function(t){return 0!==e[t]}))){for(o in a)c.o(a,o)&&(c.m[o]=a[o]);if(s)var l=s(c)}for(t&&t(n);u<i.length;u++)r=i[u],c.o(e,r)&&e[r]&&e[r][0](),e[r]=0;return c.O(l)},n=self.webpackWcShipmentsBlocksJsonp=self.webpackWcShipmentsBlocksJsonp||[];n.forEach(t.bind(null,0)),n.push=t.bind(null,n.push.bind(n))}();var o=c.O(void 0,[352],(function(){return c(387)}));o=c.O(o),(window.wcGzdShipments=window.wcGzdShipments||{})["checkout-pickup-location-select"]=o}();