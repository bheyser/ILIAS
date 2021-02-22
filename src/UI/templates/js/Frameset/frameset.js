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
        },
        afterResizeCallback: function() {}
      };

      var that = this;

      var mainFrameElement = null;
      var leftFrameElement = null;
      var rightFrameElement = null;
      var curResizeFrameElement = null;

      var config = $.extend(defaults , options);

      function initFrameset() {

        mainFrameElement = $(that).find('.mainFrame');

        //$(mainFrameElement).css('margin-left', config.leftFrame.initWidth);
        //$(mainFrameElement).css('margin-right', config.rightFrame.initWidth);

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

        $(that).find('.toggle').each(function(pos, elem) {
          $(elem).click(toggleSideFrame);
        });

        $(that).css('visibility', 'visible');
      }

      function toggleSideFrame(e) {

        var toggle = e.target;

        toggleToggleGlyph(toggle);

        if( $(toggle).hasClass('toggle-left') && $(toggle).hasClass('toggle-open') )
        {
          $(leftFrameElement).show();

          setFrameHiddenCookie(
            $(that).attr('id'), getFrameClass(leftFrameElement), false
          );
        }
        else if( $(toggle).hasClass('toggle-left') && $(toggle).hasClass('toggle-closed') )
        {
          $(leftFrameElement).hide();

          setFrameHiddenCookie(
            $(that).attr('id'), getFrameClass(leftFrameElement), true
          );
        }
        else if( $(toggle).hasClass('toggle-right') && $(toggle).hasClass('toggle-open') )
        {
          $(rightFrameElement).show();

          setFrameHiddenCookie(
            $(that).attr('id'), getFrameClass(rightFrameElement), false
          );
        }
        else if( $(toggle).hasClass('toggle-right') && $(toggle).hasClass('toggle-closed') )
        {
          $(rightFrameElement).hide();

          setFrameHiddenCookie(
            $(that).attr('id'), getFrameClass(rightFrameElement), true
          );
        }
      }

      function toggleToggleGlyph(toggle)
      {
        if( $(toggle).hasClass('toggle-open') )
        {
          $(toggle).removeClass('toggle-open');
          $(toggle).addClass('toggle-closed');
        }
        else
        {
          $(toggle).removeClass('toggle-closed');
          $(toggle).addClass('toggle-open');
        }
      }

      function getFrameClass(frameElement)
      {
        if( $(frameElement).hasClass('mainFrame') )
        {
          return 'mainFrame';
        }
        else if( $(frameElement).hasClass('leftFrame') )
        {
          return 'leftFrame';
        }
        else if( $(frameElement).hasClass('rightFrame') )
        {
          return 'rightFrame';
        }

        return '';
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
        }
        else if( $(curResizeFrameElement).hasClass('rightFrame') )
        {
          newFrameResizeWidth = (
            $(curResizeFrameElement).offset().left - e.clientX + $(curResizeFrameElement).width()
          );

          newFrameResizeWidth += 30; // due to suboptimal margin/padding css

          newMainFrameWidth = mainFrameWidth - newFrameResizeWidth - leftFrameWidth;

          if( newMainFrameWidth <= parseInt(config.mainFrame.minWidth) )
          {
            return false;
          }
        }

        $(curResizeFrameElement).css('width', newFrameResizeWidth + 'px');

        setFrameResizeCookie(
          $(that).attr('id'), getFrameClass(curResizeFrameElement), newFrameResizeWidth
        );

        config.afterResizeCallback(e, $(that).attr('id'), getFrameClass(curResizeFrameElement));
      }

      function setFrameResizeCookie(framesetId, frameClass, frameWidth)
      {
        $.cookie(framesetId + '_' + frameClass + '_width', parseInt(frameWidth) + 'px');
      }

      function setFrameHiddenCookie(framesetId, frameClass, frameHidden)
      {
        $.cookie(framesetId + '_' + frameClass + '_hidden', frameHidden ? 1 : 0);
      }

      initFrameset();

      return this;
    };

  });
})(jQuery);

