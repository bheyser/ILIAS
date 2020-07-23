(function($){
  $(document).ready(function() {

    $.fn.frameset = function(options) {

      var defaults = {
        leftFrame: {
          minWidth: '100px',
          initWidth: '100px'
        },
        rightFrame: {
          minWidth: '100px',
          initWidth: '100px'
        },
        mainFrame: {
          minWidth: '100px',
        }
      };

      var that = this;

      var mainFrameElement = null;
      var leftFrameElement = null;
      var rightFrameElement = null;
      var curResizeFrameElement = null;

      var config = $.extend(defaults , options);

      function initFrameset() {

        console.log(config);

        mainFrameElement = $(this).find('.content');

        $(that).find('aside.leftFrame').each(function(pos, elem) {
          var resizer = buildResizerElement();
          resizer.addEventListener('mousedown', initResize, false);
          elem.appendChild(resizer);
          $(elem).css('width', config.leftFrame.initWidth);
          $(elem).css('min-width', config.leftFrame.minWidth);
          leftFrameElement = elem;
        });

        $(that).find('aside.rightFrame').each(function(pos, elem) {
          var resizer = buildResizerElement();
          resizer.addEventListener('mousedown', initResize, false);
          elem.appendChild(resizer);
          $(elem).css('width', config.rightFrame.initWidth);
          $(elem).css('min-width', config.rightFrame.minWidth);
          rightFrameElement = elem;
        });

        $(that).css('visibility', 'visible');
      }

      function buildResizerElement() {
        var resizer = document.createElement('div');
        resizer.className = 'draghandle';
        resizer.style.width = '5px';
        resizer.style.height = '100vh';
        return resizer;
      }

      function initResize(e) {
        curResizeFrameElement = $(e.target).parent();
        window.addEventListener('mousemove', performResize, false);
        window.addEventListener('mouseup', stopResize, false);
      }

      function stopResize(e) {
        curResizeFrameElement = null;
        window.removeEventListener('mousemove', performResize, false);
        window.removeEventListener('mouseup', stopResize, false);
      }

      function performResize(e) {

        var mainFrameWidth = parseInt($(that).css('width'));
        var leftFrameWidth = parseInt($(leftFrameElement).css('width'));
        var rightFrameWidth = parseInt($(rightFrameElement).css('width'));

        var newMainFrameWidth = null;
        var newFrameResizeWidth = null;

        if( $(curResizeFrameElement).hasClass('leftFrame') )
        {
          newFrameResizeWidth = (
            e.clientX - $(curResizeFrameElement).offset().left
          );

          newMainFrameWidth = mainFrameWidth - newFrameResizeWidth - rightFrameWidth;

          if( newMainFrameWidth <= parseInt(config.mainFrame.minWidth) )
          {
            return false;
          }

          mainFrameElement.css('margin-left', newFrameResizeWidth + 'px');
        }
        else if( $(curResizeFrameElement).hasClass('rightFrame') )
        {
          newFrameResizeWidth = (
            $(curResizeFrameElement).offset().left - e.clientX + $(curResizeFrameElement).width()
          );

          newMainFrameWidth = mainFrameWidth - newFrameResizeWidth - leftFrameWidth;

          if( newMainFrameWidth <= parseInt(config.mainFrame.minWidth) )
          {
            return false;
          }

          mainFrameElement.css('margin-right', newFrameResizeWidth + 'px');
        }

        $(curResizeFrameElement).css('width', newFrameResizeWidth + 'px');
      }

      initFrameset();

      return this;
    };

  });
})(jQuery);

