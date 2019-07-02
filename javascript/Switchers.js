var Switchers = {
	list: {},
	place: function(p) {
		if (p.once && this.list[p.id]){
			return this.list[p.id];
		}
		var but = new Switcher(p);
		this.list[but.id] = but;
		return but;
	}
};


var Switcher = function(p) {

	this.hide = function() {
		this.$.hide();
	}

	this.show = function() {
		this.$.show();
	}

	/*	
	 id
	 host
	 cls - outer class (unselected buttons)
	 css - outer css
	 but [
	 {	
	 title
	 unselected : {
	 cls - switched button css class
	 css - switched css mode
	 first - {
	 cls
	 css
	 }
	 last - {
	 cls
	 css
	 }
	 }
	 selected : {
	 cls 
	 css
	 }	
	 action : function 
	 },{
	 title
	 ...
	 action : function 
	 }
	 ],
	 cur - current selected item
	 */

	this.place = function(p) {

		var id = p.id || Math.round(Math.random() * 10000);
		this.host = p.host || $(document.body);
		this.id = id;
		var that = this;

		if ($('.switcher_' + id).length) {
			if (p.id) {
				console.error('Object with ' + id + ' already present');
				return false;
			}
			that.place(p); //if auto generated id already present
			return false;
		}

		if (!p.but || p.but.length == 0) {
			console.error('Need to set up buttons in switcher');
		}

		this.cur = p.cur || 0;
		this.selected = p.selected;
		this.but = p.but;
		var h = [];
		var w = Math.round(100 / p.but.length);

		for (var i = 0; i < p.but.length; i++) {
			h.push('<td id="switch_' + id + '_item_' + i + '" style="width:' + w + '%" class="switch_item ' +
					(p.unselected || '') +
					(
							i == 0 && p.first
							? ' ' + p.first
							: ''
							) +
					(
							(i == p.but.length - 1) && p.last
							? ' ' + p.last
							: ''
							) + (
					i == this.cur
					? ' ' + p.selected
					: ''
					) +
					'">' + p.but[i].title + '</td>');
		}

		p.host.html('<table class="' + (p.cls || '') + '"><tr>' + h.join('') + '</tr></table>');

		//set controller

		var that = this;

		$('.switch_item', this.host).unbind('mousedown').mousedown(function() {
			var id = $(this).attr('id').split('_item_')[1];
			/*
			 if (id == that.cur) {
			 //
			 } else {
			 that.cur = id;
			 $('.switch_item', that.host).removeClass(that.selected);
			 $(this).addClass(that.selected);
			 if (that.but[id] && that.but[id].action) {
			 that.but[id].action(id);
			 } else {
			 //console.log($(this).attr('id'), id, that.but);
			 }
			 }*/

			var obj = $(this);
			that.switch(id);
		});
	};

	this.switch = function(to, noaction) {
		//console.log('switch',to,this.cur)

		if (to != this.cur) {
			this.cur = to;
			if (this.but[to] && this.but[to].action && !noaction) {
				this.but[to].action(to);
			}
			$('.switch_item', this.host).removeClass(this.selected);
			$('#switch_' + this.id + '_item_' + to + '').addClass(this.selected);
		}
	};

	this.place(p);
};