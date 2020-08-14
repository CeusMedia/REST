function App(options) {
	this.options = jQuery.extend({
		serverUrl: 'http://localhost:1080/libs/REST/demo/Server/',
		serverUsername: '',
		serverPassword: '',
		verbose: false,
	}, options);
	if(this.options.verbose)
		console.log({appOptions: this.options});
	this.client = new RestClient(this.options.serverUrl);
	this.client.setVerbose(this.options.verbose);
	this.client.setBasicAuth(this.options.serverUsername, this.options.serverPassword );
	this.resources	= {};
	this.run = function() {
		var resourceTest = new ResourceTest(this);
		resourceTest.index();
//		this.get('test', {}, function(r){console.log(r);});
//		console.log('Hello!');
	}
	this.setBasicAuth = function( username, password ){
	}
	this.addResource = function(id, resource) {
		this.resources[id] = resource;
	}
	this.handle = function(resource, action, parameters, arguments, data, callback){
		if( typeof this.resources[resource] === "undefined" ){
			throw "No resource registered by ID: " + resource;
		}
		this.resources[resource].action(parameters, arguments, data, callback);
	}
};
