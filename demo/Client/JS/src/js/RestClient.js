function RestClient(url) {
    this.url = url;
	this.basicAuth = null;
	this.verbose = false;

	this.setBasicAuth = function(username, password){
		this.basicAuth	= {username: username, password: password};
	}

	this.setVerbose = function(verbose){
		this.verbose = verbose;
	}

	this.request = function(method, options, params, data, callback){
		var basicOptions = {
			method: method,
			crossDomain: true,
			dataType: "json",
			contentType: "application/x-www-form-urlencoded; charset=UTF-8",
			error: function(a, b){
				console.log({a: a, b: b});
			},
			headers: {},
			success: callback
		}
/*		if( this.basicAuth ){
			basicOptions.username = this.basicAuth.username;
			basicOptions.password = this.basicAuth.password;
		}*/
		if( this.basicAuth )
			basicOptions.headers.Authorization = 'Basic ' + btoa(this.basicAuth.username + ':' + this.basicAuth.password );
		var options = jQuery.extend(basicOptions, options);
		if( this.verbose )
			console.log({requestOptions: options});
		jQuery.ajax(options);
	}

	this.get = function(path, params, callback){
		this.request('GET', {url: this.url + path}, params, {}, callback);
	}

	this.post = function(path, params, data, callback){
		this.request('POST', {url: this.url + path}, params, data, callback);
	}

	this.put = function(path, params, data, callback){
		this.request('PUT', {url: this.url + path}, params, data, callback);
	}

	this.delete = function(path, params, callback){
		this.request('DELETE', {url: this.url + path}, params, {}, callback);
	}
};
