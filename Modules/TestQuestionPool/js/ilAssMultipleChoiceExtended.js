/* MC-MR Js Engines */

(function($){

    /* fau: testNav - handle the "none above" mc option in special tests. */

    function handleMultipleChoiceResult()
    {
        if ($('.ilAssMultipleChoiceResult:checked').length > 0)
        {
            $('.ilAssMultipleChoiceNone').removeAttr('checked');
        }
        else
        {
            $('.ilAssMultipleChoiceNone').attr('checked','checked');
        }
    }

    function handleMultipleChoiceNone()
    {
        if ($('.ilAssMultipleChoiceNone:checked').length > 0)
        {
            $('.ilAssMultipleChoiceResult').removeAttr('checked');
        }
    }

    $( document ).ready(
        function()
        {
            $('.ilAssMultipleChoiceResult').change(handleMultipleChoiceResult);
            $('.ilAssMultipleChoiceNone').change(handleMultipleChoiceNone);
        }
    );

}(jQuery));

(function($){

    /* mcSelLim - handle the mc selection limit */

    var instances = new Array();

    $.fn.ilAssMultipleChoiceEngine = function(questionId, options)
    {
        options = jQuery.extend({}, jQuery.fn.ilAssMultipleChoiceEngine.defaults, options);

        instances[questionId] = new _ilAssMultipleChoiceEngine(questionId, options);

        return instances[questionId];
    };
// PATCH-BEGIN: excludeMcOptions
    $.fn.ilAssMultipleChoiceEngine.defaults = {
        qstContainerSelectorBase: 'div.ilc_question_MultipleChoice',
        mcOptionsSelector: 'input.ilAssMultipleChoiceOption',
        exclusionToggleBtnSelector: 'a.mcExclusionToggle',
        exclusionToggleBtnLabels: {
            exclude_option_button: 'Exclude Answer',
            include_option_button: 'Reset Exclusion'
        },
        excludedMcOptions: [],
        excludedMcOptionsInputName: 'excluded_mc_options',
        minSelection: null,
        maxSelection: null
    };

    var _ilAssMultipleChoiceEngine = function(questionId, options)
    {
        this.questionId = questionId;
        this.options = options;
    };

    _ilAssMultipleChoiceEngine.prototype = {
        init: function()
        {
            this.detachExclusionToggleHandler();
            this.attachExclusionToggleHandler();

            if( this.options.maxSelection )
            {
                this.detachSelectionChangeHandler();
                this.attachSelectionChangeHandler();
            }
        },

        attachExclusionToggleHandler: function()
        {
            $(buildExclusionToggleSelector(this)).on('click', handleExclusionToggle);
        },

        detachExclusionToggleHandler: function()
        {
            $(buildExclusionToggleSelector(this)).off('click');
        },

        attachSelectionChangeHandler: function()
        {
            $(buildAllChoiceOptionSelector(this)+':enabled').each(
                function(pos, item)
                {
                    $(item).on('change', handleSelectionChange);
                }
            )
        },

        detachSelectionChangeHandler: function()
        {
            $(buildAllChoiceOptionSelector(this)).each(
                function(pos, item)
                {
                    $(item).off('change');
                }
            );
        },

        updateExcludedMcOptionsInput: function()
        {
            $(buildExcludedMcOptionsInputSelector(this)).val(
                JSON.stringify(this.options.excludedMcOptions)
            );
        },

        setOptionExcluded: function(optionIndex)
        {
            this.options.excludedMcOptions.push(optionIndex);

            this.updateExcludedMcOptionsInput();
        },

        unsetOptionExcluded: function(optionIndex)
        {
            this.options.excludedMcOptions.splice(
                this.options.excludedMcOptions.indexOf(optionIndex), 1
            );

            this.updateExcludedMcOptionsInput();
        },

        exclusionToggled: function(triggerElement)
        {
            var optionIndex = fetchOptionIndex(triggerElement);
            var optionInput = $(buildChoiceOptionSelector(this, optionIndex));

            if( $.inArray(optionIndex, this.options.excludedMcOptions) > -1 )
            {
                this.unsetOptionExcluded(optionIndex);
                this.updateOptionIncluded(optionInput);
            }
            else
            {
                this.setOptionExcluded(optionIndex);
                this.updateOptionExcluded(optionInput);
            }

            this.selectionChanged();
        },

        selectionChanged: function()
        {
            if( this.isSelectionLimitReached() )
            {
                this.disableUnselectedOptions();
            }
            else
            {
                this.enableUnselectedOptions();
            }

            this.detachSelectionChangeHandler();
            this.attachSelectionChangeHandler();
        },

        isSelectionLimitReached: function()
        {
            var maxSelection = this.options.maxSelection;

            if( !maxSelection )
            {
                return false;
            }

            var numSelected = $(buildAllChoiceOptionSelector(this)+':checked').length;

            return  numSelected >= maxSelection;
        },

        enableUnselectedOptions: function(questionId)
        {
            var excludedOptions = this.options.excludedMcOptions;

            $(buildAllChoiceOptionSelector(this)+':disabled').each(
                function(pos, item)
                {
                    if( $.inArray(fetchOptionIndex(item), excludedOptions) < 0 )
                    {
                        $(item).removeAttr('disabled');
                    }
                }
            );
        },

        disableUnselectedOptions: function()
        {
            $(buildAllChoiceOptionSelector(this)+':not(:checked)').each(
                function(pos, item)
                {
                    $(item).attr('disabled', 'disabled');
                }
            );
        },

        updateOptionExcluded: function(optionInput)
        {
            $(optionInput).removeAttr('checked');
            $(optionInput).attr('disabled', 'disabled');

            var optionLabel = $( $(optionInput).parents('div.ilc_qanswer_Answer').find('label') );
            optionLabel.addClass('mcOptionExcluded');

            var toggle = $( $(optionInput).parents('div.ilc_qanswer_Answer').find(this.options.exclusionToggleBtnSelector) );
            toggle.addClass('mcExclusionToggleInverted');
            toggle.html('&nbsp;' + this.options.exclusionToggleBtnLabels.include_option_button + '&nbsp;');
        },

        updateOptionIncluded: function(optionInput)
        {
            if( !this.isSelectionLimitReached() )
            {
                $(optionInput).removeAttr('disabled');
            }

            var optionLabel = $( $(optionInput).parents('div.ilc_qanswer_Answer').find('label') );
            optionLabel.removeClass('mcOptionExcluded');

            var toggle = $( $(optionInput).parents('div.ilc_qanswer_Answer').find(this.options.exclusionToggleBtnSelector) );
            toggle.removeClass('mcExclusionToggleInverted');
            toggle.html('&nbsp;' + this.options.exclusionToggleBtnLabels.exclude_option_button + '&nbsp;');
        }
    };

    var handleExclusionToggle = function(e)
    {
        instances[fetchQuestionId(this)].exclusionToggled(this);

        e.preventDefault();
        e.stopPropagation();
        return false;
    };

    var handleSelectionChange = function()
    {
        instances[fetchQuestionId(this)].selectionChanged();
    };

    var getQuestionIdAttributeName = function()
    {
        return 'data-qst-id';
    };

    var getOptionIndexAttributeName = function()
    {
        return 'data-ans-id';
    };

    var fetchQuestionId = function(triggerElement)
    {
        return $(triggerElement).attr(getQuestionIdAttributeName());
    };

    var fetchOptionIndex = function(triggerElement)
    {
        return $(triggerElement).attr(getOptionIndexAttributeName());
    };

    var buildQuestionContainerSelector = function(instance)
    {
        return instance.options.qstContainerSelectorBase + '[' + getQuestionIdAttributeName() + '=' + instance.questionId + ']';
    };

    var buildAllChoiceOptionSelector = function(instance)
    {
        return buildQuestionContainerSelector(instance) + ' ' + instance.options.mcOptionsSelector;
    };

    var buildChoiceOptionSelector = function(instance, optionIndex)
    {
        return buildQuestionContainerSelector(instance) + ' ' + instance.options.mcOptionsSelector + '[' + getOptionIndexAttributeName() + '=' + optionIndex + ']';
    };

    var buildExclusionToggleSelector = function(instance)
    {
        return buildQuestionContainerSelector(instance) + ' ' + instance.options.exclusionToggleBtnSelector;
    };

    var buildExcludedMcOptionsInputSelector = function(instance)
    {
        return buildQuestionContainerSelector(instance) + ' input[name=' + instance.options.excludedMcOptionsInputName + ']';
    }

// PATCH-BEGIN: excludeMcOptions
}(jQuery));
