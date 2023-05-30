<?php
class View_Test_Add extends View
{
	public function render(): string
	{
		$content	= '
<div class="content-panel">
	<h3><span class="muted">Test:</span> Add</h3>
	<div class="content-panel-inner">
		<form action="./Test/add" method="post">
			<div class="row-fluid">
				<div class="span4">
					<label>Key</label><br/>
					<input type="text" name="key[]" value="" class="span12"/>
				</div>
				<div class="span8">
					<label>Value</label><br/>
					<input type="text" name="value[]" value="" class="span12"/>
				</div>
			</div>

			<div class="row-fluid">
				<div class="span4">
					<label>Key</label><br/>
					<input type="text" name="key[]" value="" class="span12"/>
				</div>
				<div class="span8">
					<label>Value</label><br/>
					<input type="text" name="value[]" value="" class="span12"/>
				</div>
			</div>

			<div class="row-fluid">
				<div class="span4">
					<label>Key</label><br/>
					<input type="text" name="key[]" value="" class="span12"/>
				</div>
				<div class="span8">
					<label>Value</label><br/>
					<input type="text" name="value[]" value="" class="span12"/>
				</div>
			</div>

			<div class="buttonbar">
				<a href="./Test" class="btn btn-small">cancel</a>
				<button type="submit" name="save" class="btn btn-primary">save</button>
			</div>
		</form>
	</div>
</div>';
		return $content;
	}
}
