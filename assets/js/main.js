document.addEventListener('DOMContentLoaded', function() {
    const imageModal = new bootstrap.Modal(document.getElementById('imageModal'));
    const modalImage = document.getElementById('modalImage');

    document.querySelectorAll('.img-zoomable').forEach(item => {
        item.addEventListener('click', event => {
            event.preventDefault();
            const fullSrc = item.getAttribute('data-src-full') || item.src;
            modalImage.src = fullSrc;
            imageModal.show();
        });
    });
});