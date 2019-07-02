/**
 * required Base.js
 * 
 * refresh : true -> remove previously set button
 * 
 */

var Buttons = {//factory method
    list: {},
    place: function(p) {
	if (p.id && this.list[p.id]) {//object already present
	    var but = this.list[p.id];
	    if (p.refresh) {
		but.remove();
		this.list[p.id] = null;
	    } else {
		but.show();
		return but;
	    }
	}
	var but = new Button(p);
	this.list[but.id] = but;
	return but;
    }
};

/*
 * new Button({
 *      class
 *      css
 *      id
 *      title
 *      action : function(){
 *          
 *      }
 * })
 * 
 */

var Button = function(p) {

    this.disabled = false;

    this.hide = function() {
	this.$.hide();
    };

    this.show = function() {
	this.$.show();
    };

    this.remove = function() {
	this.$.remove();
    };

    this.enable = function() {
	this.$.css({
	    opacity: 1
	});
	this.disabled = false;
    };

    this.disable = function(foo) {
	this.$.css({
	    opacity: 0.5
	});
	this.disabled = foo
		? foo
		: 1;
    };

    this.place = function(p) {
	var id = p.id || Math.round(Math.random() * 10000);

	this.host = p.host || document.body;

	this.id = id;

	var that = this;

	if ($('.button_' + id).length) {
	    if (p.id) {
		console.error('Object with ' + id + ' already present');
		return false;
	    }
	    that.place(p); //this if generated id already present
	    return false;
	}

	var obj = $('.raw_button_host').clone();

	$('.raw_button', obj).removeClass('raw_button').addClass('button_' + id);
	obj.removeClass('raw_button_host').addClass('button_host_' + id).appendTo(this.host);

	obj.css({
	    display: 'inline-block'
	}).show();

	this.$ = $('.button_' + id);

	this.$.html(p.title);

	//add css 

	if (p.outer) {
	    if (p.outer.css) {
		$('.button_host_' + id).css(p.outer.css);
	    }
	}

	if (p.css) {
	    this.$.css(p.css);
	}

	if (p.class) {
	    if (p.class.join) {
		this.$.addClass(p.class.join(' '));
	    } else {
		this.$.addClass(p.class);
	    }
	}

	//set controller

	this.$.unbind('mousedown').mousedown(function() {
	    if (!this.disabled) {
		Base.press($(this));
		if (p.action) {
		    p.action(that.id);
		}
	    } else {
		if (typeof this.disabled === 'function') {
		    Base.press($(this));
		    this.disabled();
		}
	    }
	});

    };

    this.place(p);

};