<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Mantis M-files Importer for Claims</title>
	<link rel="stylesheet" type="text/css" href="css/dropzone-5.5.0.min.css" />
	<script type="text/javascript" src="js/dropzone-5.5.0.min.js"></script>
	<style type="text/css">
	.dropzone,.dropzone * {
		box-sizing: border-box;
	}

	.dropzone {
		position: relative;
	}

	.dropzone .dz-preview {
		position: relative;
		display: inline-block;
		width: 120px;
		margin: 0.5em;
	}

	.dropzone .dz-preview .dz-progress {
		display: block;
		height: 15px;
		border: 1px solid #aaa;
	}

	.dropzone .dz-preview .dz-progress .dz-upload {
		display: block;
		height: 100%;
		width: 0;
		background: green;
	}

	.dropzone .dz-preview .dz-error-message {
		color: red;
		display: none;
	}

	.dropzone .dz-preview.dz-error .dz-error-message,.dropzone .dz-preview.dz-error .dz-error-mark
		{
		display: block;
	}

	.dropzone .dz-preview.dz-success .dz-success-mark {
		display: block;
	}

	.dropzone .dz-preview .dz-error-mark,.dropzone .dz-preview .dz-success-mark
		{
		position: absolute;
		display: none;
		left: 30px;
		top: 30px;
		width: 54px;
		height: 58px;
		left: 50%;
		margin-left: -27px;
	}

	.decoration {
		border: 1px dashed #999;
		background: #f2f2f2;
		padding: 20px;
	}
	
	.container {
		width: 50%;
		margin: auto;
	}
	
	h1, p {
		text-align: center;
	}
	</style>
</head>

<body>
	<div class="container">
		<h1>Mantis M-files Importer for Claims</h1>
		<p>Drag documents from your M-files and drop to the box below, issues will be created on Mantis</p>
		<form action="claims_mfiles_upload_process.php" class="dropzone decoration" id="my-awesome-dropzone"></form>
	</div>

	<script type="text/javascript">
		Dropzone.options.myAwesomeDropzone =
		{
			maxFilesize: 200,
			addRemoveLinks: true,
			removedfile: function(file)
			{ 
				var fileRef;
				return (fileRef = file.previewElement) != null ? fileRef.parentNode.removeChild(file.previewElement) : void 0;
			},
			success: function(file, response)
			{
				alert(response);
			},
			error: function(file, response)
			{
				alert(response);
			}
		};
	</script>
</body>
</html>