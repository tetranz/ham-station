var hamstationApp = (function ($) {

    // ----- UI Controller -----
    const uiController = (function () {

        function selectQueryType(queryType) {
            const findClass = `query-input-${queryType}`;
            $('.query-input').each((index, element) => {
                let $element = $(element);
                if ($element.hasClass(findClass)) {
                    $element.removeClass('hidden');
                }
                else {
                    $element.addClass('hidden');
                }
            });
        }

        return {
            selectQueryType: selectQueryType
        };

    })();

    // ----- Main controller -----
    const controller = (function (uiCtrl) {
        let context;
        var setupEventListeners = () => {
            $('input[type=radio][name=query_type]', context).change(e => {
                uiCtrl.selectQueryType(e.target.value);
            });
        }

        return {
          'init': (ctx) => {
              context = ctx;
              setupEventListeners();
          }
        };
    })(uiController);

    return {
        'init': controller.init
    };
})(jQuery);


(function (Drupal) {
    Drupal.behaviors.hamstation = {
        attach: context => hamstationApp.init(context)
    };
})(Drupal);
