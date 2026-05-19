(function ($) {
  function mountShadowForms() {
    var hosts = document.querySelectorAll('.lfb-shadow-host[data-lfb-shadow="1"]');

    hosts.forEach(function (host) {
      if (host.dataset.lfbMounted === '1') {
        return;
      }

      var shadow = host.attachShadow({ mode: 'open' });
      var template = host.querySelector('template');
      if (!template) {
        return;
      }

      var css = host.dataset.lfbCss || '';
      var js = host.dataset.lfbJs || '';

      var style = document.createElement('style');
      style.textContent = ':host{display:block;max-width:100%;} .lfb-form-inner{width:100%;} .lfb-form-inner *{box-sizing:border-box;} @media (max-width:767px){.lfb-form-inner *{max-width:100%;}} ' + css;
      shadow.appendChild(style);
      shadow.appendChild(template.content.cloneNode(true));

      var form = shadow.querySelector('.lfb-form-inner');
      if (form) {
        form.setAttribute('novalidate', 'novalidate');
      }

      if (js) {
        try {
          (new Function(js))();
        } catch (e) {
          // Keep frontend stable if custom JS has an error.
        }
      }

      host.dataset.lfbMounted = '1';
    });
  }

  $(document).ready(function () {
    $('.lfb-form-inner').attr('novalidate', 'novalidate');
    mountShadowForms();
  });
})(jQuery);
