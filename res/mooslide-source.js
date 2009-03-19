/*
Script: mooslide.js
	mooslide - my object oriented sliderscript.

License:
	MIT-style license.

MooSlide Copyright:
	copyright (c) 2008 Michael 'Iggy' Rudolph <info@sensomedia.de>, http://www.sensomedia.de

MooSlide Credits:
	Class is roughly based on class.noobSlide.js <http://www.efectorelativo.net/laboratory/noobSlide/> (c) ? luistar15,
	License: MIT-style license.
*/

/*
Class: mooSlide
	This is the basic mooSlide class.
	Creates a new class, its initialize method will fire upon class instantiation.

Arguments:
	properties - the collection of properties that apply to the class.
		container: dom element | the div container of the slider | required
		mode: string | "h" horizontal, "v" vertical | default: "h" 
		items: object/array | required
		size: int | width or height in px, depends on mode | default: 500
		startitem: int | index of item to set the initial strt position to | default: 0 
		autorun: boolean | default false
		direction: string | walk to "next" or "previous" | default: "next" 
		interval: int | delay in ms from transition to transition | default: 5000
		FxStyle: object | transition configuration for Fx.Style | default: { duration:750, wait:false }
		onWalk: event | callback fn for walk event. calls with (items[currpos], handles[currpos]) | default: null
		pauseonmouse: boolean | pauses the sliding while mouse is over the ticker area | default true
	
Methods:
	previous(): decrease current position and move there
	next(): increase current position and move there
	play ( interval, direction, wait): calling 'direction' every 'interval'ms 
		interval: int | delay in ms | required
		direction: string | method to call: "previous" or "next" | required
		wait: boolean | wait till transition finished | required
	stop(): stops an autorun (initialized by "play" or autorun=true)
	walk( item ): move to item position with transition
		item: int | required
	
Example:
	(start code)
	var myslide = new mooSlide({
		mode: 'h',
		items: ['0','1','2','3'],
		size: 400,
		container: $('mycontainer'),
		startitem: 3,
		autorun: false,
		direction: 'previous',
		interval: 4000,
		FxStyle: {
			duration: 800,
			transition: Fx.Transitions.Cubic.easeOut,
			wait: false
		}
	});
	myslide.play( 5000, 'previous', true);
	------------
	<div id="mycontainer">
		<div>item0</div>
		<div>item1</div>
		<div>item2</div>
		<div>item3</div>
	</div>		
	(end)
*/


var mooSlide = new Class({

	initialize: function( properties ){

		this.container		= properties.container;
		this.mode			= properties.mode || 'h'; 
		this.items			= properties.items;
		this.size			= properties.size || 500;
		this.startitem		= properties.startitem || 0;
		this.autorun		= properties.autorun || false;
		this.direction		= properties.direction || 'next';
		this.interval		= properties.interval || 5000;
		this.FxStyle		= properties.FxStyle || { duration:500, transition: Fx.Transitions.Circ, wait:true };
		this.onWalk 		= properties.onWalk || null;
		this.pauseonmouse 	= properties.pauseonmouse || true;
		this.currpos		= null;
		this.prevpos		= null;
		this.nextpos		= null;
		this.runner			= null;

		// array with css properties for the respective direction mode 		
		this.dirCSSProps = {h:['left','width'], v:['top','height']};
		
		/*
		to create a way of 'seamless' transitioning from the first to the last item in the list, 
		we have to create two more divs, the last one needs to be cloned in front of the first one,
		and the original first div needs to be cloned to a position after the last on, like this
		before: A | B | C | D
		after: D(cloned) | A | B | C | D | A(cloned)
		*/
		// first add two empty items
		this.items.push('','');
		// get first & last div from container
		firstdiv = $(this.container).getFirst();
		lastdiv = $(this.container).getLast();
		// clone them to the respective oposite side
		firstdiv.clone().injectInside( $(this.container) );
		lastdiv.clone().injectTop( $(this.container) );
		
		// set current position to startingposition +1 (to compensate the cloned divs) 
		this.currpos = (this.startitem > this.items.length-3)? 1 : this.startitem+1 ;
		// initialize container size (width or height, depends on direction)
		this.container.setStyle( this.dirCSSProps[this.mode][1], (this.size * this.items.length)+'px' );
		// set container to starting position
		this.container.setStyle( this.dirCSSProps[this.mode][0], -this.currpos * this.size );
		// add a pause event for the container
		
		// get fx.style class for the transition  depending on the mootools version
		var $mooversion = MooTools['version'].split('.');
		if ($mooversion[0] == 1 && $mooversion[1] == 2) {
			this.fx = new Fx.Tween(
				this.container,
				$extend( (this.FxStyle), { property:this.dirCSSProps[this.mode][0] } )
			);
		} else {
			this.fx = new Fx.Style( 
				this.container,
				this.dirCSSProps[this.mode][0],
				this.FxStyle
			);
		}
		
		this.button_event = properties.button_event || 'click';
		this.handle_event = properties.handle_event || 'click';
		this.buttons = {previous: [], next: [], play: [], playback: [], stop: []};
		if( properties.buttons ){
			for(var action in properties.buttons){
				this.addActionButtons(action, $type(properties.buttons[action])=='array' ? properties.buttons[action] : [properties.buttons[action]]);
			}
		};
		
		if( properties.autorun ) {
			this.play( this.interval, this.direction, true );
		};

	},
	mousepause: function() {
		if (this.pauseonmouse) {
			this.container.getParent('div').addEvents({
				'mouseenter': this.pause.bind(this),
				'mouseleave': this.resume.bind(this)
			});
			this.pauseonmouse = false;
		}
		
	},
	previous: function( stopautorun ){
		this.stop();
		this.currpos += (this.currpos > 0) ? -1 : this.items.length-1;
		this.walk( null, stopautorun );
	},

	next: function( stopautorun ){
		this.currpos += (this.currpos < this.items.length-1) ? 1 : 1-this.items.length;
		this.walk( null, stopautorun );
	},

	play: function( interval, direction, wait ){
		if( !wait ){
			this[direction](false);
		};
		this.runner = this[direction].periodical( interval, this, true );
	},

	pause: function( ){
		this.fx.pause();
		this.stop();
	},
	resume: function( ){
		this.fx.resume();
		this.stop();
		if( this.autorun ){ 
			this.play( this.interval, this.direction, true );
		};
	},
	
	stop: function(){
		$clear( this.runner );
	},

	walk: function( item, stopautorun ){
		if( $defined(item) ){
			if( item == this.currpos) return;
			this.currpos = item;
		};
		// when at first item (which is a clone of the last 'real' item) move immediately to the original item and do the transition from there 
		if( this.currpos == 0 ) {
			this.container.setStyle( this.dirCSSProps[this.mode][0], -(this.items.length-1) * this.size);
			this.currpos = this.items.length-2;
		};
		// when at last item (which is a clone of the first 'real' item) move immediately to the original item and do the transition from there 
		if( this.currpos == this.items.length-1 ) {
			this.container.setStyle( this.dirCSSProps[this.mode][0], 0 );
			this.currpos = 1;
		};
		// calculate previous and next position
		this.prevpos = this.currpos + ( this.currpos > 0 ? -1 : this.items.length-1 );
		this.nextpos = this.currpos + ( this.currpos < this.items.length-1 ? 1 : 1-this.items.length );

		// if stopautorun is set (eg. by clicking prev/next buttons), the _runner will be cleared
		if( stopautorun ){
			this.stop();
		};
		
		// trigger adding of mousepause event handlers 
		this.mousepause();
		
		// start transition (move to position)
		this.fx.start( -this.currpos * this.size );

		// initialize the onWalk event. supplies the current item and the current handle
		if( this.onWalk ){
			this.onWalk( 
				this.items[this.currpos] || null,
				(this.handles && this.handles[this.currpos] ? this.handles[this.currpos] : null)
			);
		};
		// if autorun is set, then start the _runner again.  
		if( stopautorun && this.autorun ){ 
			this.play( this.interval, this.direction, true );
		};
		
	},
	
	addActionButtons: function( action, buttons ){
		for(var i=0; i<buttons.length; i++){
			switch(action){
				case 'previous':
					buttons[i].addEvent( 
						this.button_event,
						this.previous.bind(this,[true])
					); 
					break;
				case 'next': 
					buttons[i].addEvent( 
						this.button_event,
						this.next.bind(this,[true])
					);
					break;
				case 'play':
					buttons[i].addEvent( 
						this.button_event,
						this.play.bind(this, [this.interval, 'next', false])
					);
					break;
				case 'playback':
					buttons[i].addEvent( 
						this.button_event, 
						this.play.bind(this, [this.interval,'previous',false])
					);
					break;
				case 'stop':
					buttons[i].addEvent( 
						this.button_event, 
						this.stop.bind(this)
					);
					break;
			}
			this.buttons[action].push(buttons[i]);
		};
	}
	
});