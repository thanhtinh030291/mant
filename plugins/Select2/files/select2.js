$.fn.select2.defaults.set('theme', 'bootstrap');
$( document ).ready( function() {
    $( 'select' ).select2( {
        containerCssClass: ":all"
    } );
} );