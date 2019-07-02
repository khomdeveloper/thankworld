var Help = {
    template: false,
    loading: false,
    show: function() {
	if (this.loading) {
	    return;
	} else {
	    this.loading = true;
	}
	var that = this;
	B.getHTML({
	    file: 'help.php',
	    selector: '.help',
	    host: $(document.body),
	    append: true,
	    once: true,
	    callback: function() {
		Base.loadRemote(Application.baseUrl + '/js_minified/jquery.transform2d.js', function() {
		    that.loading = false;

		    that.arrow({
			left: '50%',
			top: '-10px',
			'margin-left': '250px',
			length: '300px',
			angle: '45deg',
			height: '156px'
		    });
			
			//overflow: hidden; position: absolute; left: 50%; top: -10px; width: 15px; height: 156px; z-index: 1; margin-left: 250px; transform: rotate(45deg);
		    
		   
		    
		    //right

		
//top

		   
/*
		    that.arrow({
			left: '50%',
			top: '91px',
			'margin-left': '-205px',
			angle: '-110deg',
			height: '80px'
		    });
*/
		});

		$('.help').fadeIn().unbind('mousedown').mousedown(function() {
		    $('.help').fadeOut();
		});
	    }
	});
    },
    /**
     *   length
     *   angle
     *	 left
     *	 top
     *   
     */
    arrow: function(input) {

	var selector = 'arrow_' + Math.round(Math.random() * 10000);

	$('.help').append('<div class="pa ' + selector + '" style="overflow:hidden; position:absolute; left:50%; top:50%; width:15px; height:150px; z-index:1;"><img class="pa" src="'+ A.baseURL() +'images/help_arrow.png" alt/></div>');

	var angle = input.angle;

	delete(input.angle);

	var css = input;

	if (angle) {
	    css.transform = 'rotate(' + angle + ')';
	}

	$('.' + selector).css(css);
    }
};