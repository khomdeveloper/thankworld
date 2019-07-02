var Geolocation = {
	lat: false,
	lng: false,
	get: function() {
		var that = this;
		if (navigator && navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(function(position) {
				that.lat = position.coords.latitude;
				that.lng = position.coords.longitude;

				B.post({
					script: 'index.php?r=user/addgeodata',
					lat: that.lat,
					lng: that.lng
				});

				setTimeout(function() {
					that.get();
				}, 360 * 1000); //1 hour interval check

			}, function(error) {
				console.log(error);
			}, {
				enableHighAccuracy: true,
				timeout: 10000,
				maximumAge: 60000
			});

		} else {
			that.lat = false;
			that.lng = false;
		}
	}
}