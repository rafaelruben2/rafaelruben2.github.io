document.querySelectorAll('[data-image-upload]').forEach((upload) => {
	const input = upload.querySelector('input[type="file"]');
	const cameraButton = upload.querySelector('[data-camera-button]');
	const fileName = document.createElement('span');

	fileName.textContent = 'Belum ada gambar dipilih';
	fileName.style.color = '#72817d';
	fileName.style.fontSize = '11px';
	fileName.style.overflow = 'hidden';
	fileName.style.textOverflow = 'ellipsis';
	fileName.style.whiteSpace = 'nowrap';
	cameraButton?.parentElement?.after(fileName);
	const imagePreview = document.createElement('img');

	imagePreview.alt = 'Preview gambar produk';
	imagePreview.style.border = '1px solid #e3e9e5';
	imagePreview.style.borderRadius = '4px';
	imagePreview.style.height = '180px';
	imagePreview.style.objectFit = 'cover';
	imagePreview.style.width = '180px';
	imagePreview.style.display = 'none';
	fileName.after(imagePreview);

	input.addEventListener('change', () => {
		const file = input.files?.[0];

		fileName.textContent = file?.name ?? 'Belum ada gambar dipilih';

		if (!file) {
			imagePreview.style.display = 'none';
			return;
		}

		imagePreview.src = URL.createObjectURL(file);
		imagePreview.style.display = 'block';
	});

	cameraButton?.addEventListener('click', () => {
		input.setAttribute('capture', 'environment');
		input.click();
	});
});
