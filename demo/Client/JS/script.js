var App = {
    serverUrl: null,
	client: null,
	run: function() {
		this.client = new RestClient(this.serverUrl);
		App.Resource.Test.index();
//		this.get('test', {}, function(r){console.log(r);});
//		console.log('Hello!');
	},
};

function RestClient(url) {

    this.url = url;

	function request(method, options, params, data, callback){
		var options = jQuery.extend({
			method: method,
			error: function(a, b){
				console.log({a: a, b: b});
			},
			success: callback
		}, options);
		jQuery.ajax(options);
	}

	this.get = function(path, params, callback){
		request('GET', {url: this.url + path}, params, {}, callback);
	}

	this.post = function(path, data, callback){
	}

	this.put = function(path, params, data, callback){
	}

	this.delete = function(path, callback){
	}
};

App.Resource = {};

App.Resource.Test = {

	index: function(){
		App.client.get('test', {}, function(response){
			var target = jQuery("#result");
			var list = jQuery("<ul></ul>");
			jQuery.each(response.items, function(){
				var that = this;
				var item = jQuery("<li></li>").html(this.id);
				item.on("click", function(){
					App.Resource.Test.get(that.id)
				});
				list.append(item);
			});
			target.html(list);
		});
	},

	get: function(testId){
		console.log(testId);
		App.client.get('test/' + new String(testId), {}, function(response){
			alert(JSON.stringify(response));
		});
	}
}
