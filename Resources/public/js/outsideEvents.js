(function($,doc,outside){
'$:nomunge';
$.map(
    'click dblclick mousemove mousedown mouseup mouseover mouseout change select submit keydown keypress keyup'.split(' '),
    function( event_name ) { jq_addOutsideEvent( event_name ); }
);
jq_addOutsideEvent( 'focusin',  'focus' + outside );
jq_addOutsideEvent( 'focusout', 'blur' + outside );
$.addOutsideEvent = jq_addOutsideEvent;
function jq_addOutsideEvent( event_name, outside_event_name ) {
  outside_event_name = outside_event_name || event_name + outside;
  var elems = $(),
      event_namespaced = event_name + '.' + outside_event_name + '-special-event';
  $.event.special[ outside_event_name ] = {
    setup: function(){
      elems = elems.add( this );
      if ( elems.length === 1 ) {
        $(doc).bind( event_namespaced, handle_event );
      }
    },
    teardown: function(){
      elems = elems.not( this );
      if ( elems.length === 0 ) {
        $(doc).unbind( event_namespaced );
      }
    },
    add: function( handleObj ) {
      var old_handler = handleObj.handler;
      handleObj.handler = function( event, elem ) {
        event.target = elem;
        old_handler.apply( this, arguments );
      };
    }
  };
  function handle_event( event ) {
    $(elems).each(function(){
      var elem = $(this);
      if ( this !== event.target && !elem.has(event.target).length ) {
        elem.triggerHandler( outside_event_name, [ event.target ] );
      }
    });
  };
};
})(jQuery,document,"outside");
