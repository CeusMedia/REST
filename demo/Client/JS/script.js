
function ResourceTest(app) {

	this.app = app;

	this.add = function(id, callback){
		var resource = this;
		this.app.client.post('test', {}, {id: id}, function(response){
			if(resource.app.options.verbose)
				console.log(JSON.stringify(response));
			resource.index(callback);
//			if(typeof callback === "function")
//				callback();
		});
	}

	this.get = function(testId, callback){
		var resource = this;
		this.app.client.get('test/' + new String(testId), {}, function(response){
			if(resource.app.options.verbose)
				console.log(JSON.stringify(response));
			if(typeof callback === "function")
				callback();
		});
	}

	this.index = function(callback){
		var resource = this;
		this.app.client.get('test', {}, function(response){
			if(resource.app.options.verbose)
				console.log({response: response});
			var target = jQuery("#result");
			var list = jQuery("<table></table>").addClass('table table-striped');
			list.append(jQuery("<tr><th>ID</th><th>Actions</th></tr>"));
			jQuery.each(response.items, function(){
				var that = this;
				var row = jQuery("<tr></tr>");
				var item = jQuery("<td></td>").html(this.id).on("click", function(){
					resource.get(that.id)
				});
				row.append(item);
				var btnRemove = jQuery("<button></button>").prop({type: 'button'}).html('remove');
				btnRemove.addClass('btn btn-small btn-danger');
				btnRemove.on("click", function(){
					resource.remove(that.id);
				});
				var item = jQuery("<td></td>").addClass('cell-actions').html(btnRemove);
				row.append(item);
				list.append(row);
			});
			target.html(list);
			if(typeof callback === "function")
				callback();
		});
	}

	this.remove = function(testId, callback){
		var resource = this;
		this.app.client.delete('test/' + testId, {}, function(response){
			if(resource.app.options.verbose)
				console.log(JSON.stringify(response));
			resource.index(callback);
		});
	}
}
