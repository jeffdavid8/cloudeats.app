/**
 * Share Page Component
 * Handles image gallery, viewer modal, navigation, URL generation, and share functionality
 */

mb.registerComponent('share-page', function($element, data) {
    const shareImageBucketDir = data.shareImageBucketDir || '';
    const images = Array.from(document.querySelectorAll('.thumb-btn img')).map(img => img.alt);
    const viewer = document.getElementById('viewer');
    const shareSelectedImage = document.getElementById('shareSelectedImage');
    const shareImageContainer = document.getElementById('shareImageContainer');
    const shareImagePrevBtn = document.getElementById('shareImagePrevBtn');
    const shareImageNextBtn = document.getElementById('shareImageNextBtn');
    const shareImageSelectBtn = document.getElementById('shareImageSelectBtn');
    const shareImageCloseBtn = document.getElementById('shareImageCloseBtn');
    const caption = document.getElementById('caption');
    const thumbnails = document.getElementById('thumbnails');
    const shareImagesGrid = document.getElementById('shareImagesGrid');
    const shareImagesGridToggleButton = document.getElementById('shareImagesGridToggleButton');

    let current = data.initialIndex || 0;
    let imageSelected = 0;

    // Event handlers
    $('#shareSelectedImage img').on('click', function() {
        current = Number(this.dataset.index);
        show(current);
    });

    $('.close_share_images_grid_btn').on('click', function() {
        hideGrid();
    });

    $('.close_share_page_btn').on('click', function() {
        $('body').removeClass('no-scroll-y');
        $('.share.page.layer').hide();
    });

    $('.copy_share_link_text_container a').on('click', function() {
        var text = $(this).closest('.copy_share_link_text_container').find('.custom_share_link').text();
        copyText(text);
        notify('Copied share link to clipboard');
    });

    $('#share_page_search_string').on('change', function() {
        updateShareUrl();
    });

    $('#share_page_bg_image').on('change', function() {
        let idx = $(this).val();
        select(idx);
        updateShareUrl();
    });

    var pageTitleTimer;
    $('.share.page .page_title').on('input', function() {
        window.clearTimeout(pageTitleTimer);
        pageTitleTimer = window.setTimeout(function(){
            updateShareUrl();
        }, 600);
    });

    function updateShareUrl(image=0) {
        const params = new URLSearchParams(window.location.search);
        let current_share_image_id = params.get('share_image') || images[$('#shareSelectedImage img').data('index')];
        var page = params.get('p') ? '&p='+params.get('p') : '';
        var facet = $('#share_page_search_string').val();
        var share_url = window.location.protocol + '//' + window.location.hostname + window.location.pathname + '?app=bibleBot';
        var page_title = $('.share.page input.page_title').val();
        var share_image_id = (image) ? image : current_share_image_id;
        
        if (facet.length) {
            share_url += '&s=' + encodeURI(facet);
        }
        if (page.length) {
            share_url += page;
        }
        if (page_title.length) {
            share_url += '&page_title=' + encodeURI(page_title);
        }
        if (share_image_id) {
            share_url += '&share_image=' + share_image_id;
        }

        $('.copy_share_link_text_container .custom_share_link').text(share_url);
    }

    function buildThumbnails() {
        images.forEach((src, i) => {
            const t = document.createElement('img');
            t.src = shareImageBucketDir + '/thumbs/' + src + '_thumb.jpg';
            t.dataset.index = i;
            t.alt = `Thumb ${i+1}`;
            t.addEventListener('click', e => {
                show(Number(t.dataset.index));
            });
            thumbnails.appendChild(t);
        });
    }

    function show(index) {
        oldIndex = index;
        if (index < 0) index = images.length - 1;
        if (index >= images.length) index = 0;
        current = index;
        const img = document.createElement('img');
        img.src = shareImageBucketDir + '/' + images[current] + '.jpg';
        img.alt = `Landscape ${current+1}`;
        img.dataset.index = index;
        img.addEventListener('click', e => {
            closeViewer();
            select(current);
        });
        img.addEventListener('load', e => {
            shareImageContainer.innerHTML = '';
            shareImageContainer.appendChild(img);
            img.classList.add('show');
        });

        caption.textContent = `Image ${current+1} of ${images.length}`;
        openViewer();
        updateActiveThumb();
    }

    function select(index) {
        current = Number(index);
        imageSelected = 1;
        const img = document.createElement('img');
        img.src = shareImageBucketDir + '/' + images[index] + '.jpg';
        img.dataset.index = index;
        img.classList.add('responsive-img');
        img.alt = `Landscape ${index+1}`;
        img.addEventListener('click', e => {
            show(Number(img.dataset.index));
        });
        img.addEventListener('load', e => {
            shareSelectedImage.innerHTML = '';
            shareSelectedImage.appendChild(img);
            img.classList.add('show');
        });
        
        console.log(shareImageBucketDir + '/' + images[current] + '.jpg');
        // set current body background image
        $('body').css('background-image', 'url(' + shareImageBucketDir + '/' + images[current] + '.jpg' + ')');

        shareImageContainer.innerHTML = '';
        $(shareSelectedImage).show();
        updateShareUrl(images[index]);
    }

    function updateActiveThumb() {
        thumbnails.querySelectorAll('img').forEach((t, idx) => {
            t.classList.toggle('active', idx === current);
            if (idx === current) {
                t.scrollIntoView({
                    behavior: 'smooth',
                    inline: 'center'
                });
            }
        });
    }

    function openViewer() {
        viewer.classList.add('active');
        shareImageContainer.focus();
    }

    function closeViewer() {
        viewer.classList.remove('active');
    }

    function showGrid() {
        $(shareImagesGrid).show();
    }

    function hideGrid() {
        $(shareImagesGrid).hide();
    }

    function next() {
        show(current + 1);
    }

    function prev() {
        show(current - 1);
    }

    // Navigation event listeners
    document.querySelectorAll('#shareImagesGrid .thumb-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            const idx = Number(btn.dataset.index);
            current = idx;
            hideGrid();
            $(shareImagesGridToggleButton).show();
            select(idx);
            $('.share.page.layer').scrollTop(0);
        });
    });

    shareImagePrevBtn.addEventListener('click', prev);
    shareImageNextBtn.addEventListener('click', next);
    shareImageCloseBtn.addEventListener('click', closeViewer);
    shareImageSelectBtn.addEventListener('click', function() {
        select(current);
        closeViewer();
    });
    shareImagesGridToggleButton.addEventListener('click', function() {
        showGrid();
    });

    // Keyboard navigation
    document.addEventListener('keydown', e => {
        if (!viewer.classList.contains('active')) return;
        if (e.key === 'ArrowRight') next();
        if (e.key === 'ArrowLeft') prev();
        if (e.key === 'Enter') {
            select(current);
            closeViewer();
        };
        if (e.key === 'Escape') closeViewer();
    });

    // Touch navigation
    let startX = 0;
    shareImageContainer.addEventListener('touchstart', e => startX = e.touches[0].clientX);
    shareImageContainer.addEventListener('touchend', e => {
        const dx = e.changedTouches[0].clientX - startX;
        if (dx > 60) prev();
        else if (dx < -60) next();
    });

    // Initialize
    buildThumbnails();
    updateShareUrl();

}, ['copyText', 'notify']);