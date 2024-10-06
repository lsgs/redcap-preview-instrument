<?php
/**
 * REDCap External Module: Preview Instrument
 * @author Luke Stevens, Murdoch Children's Research Institute
 */
namespace MCRI\PreviewInstrument;

use ExternalModules\AbstractExternalModule;

class PreviewInstrument extends AbstractExternalModule
{
    protected const maxNumRecordsHideDropdowns = 10000;
    protected static $SuppressElements = array(
        '#west','#subheader','#dataEntryTopOptions','#formtop-div','#inviteFollowupSurveyBtn','#formSaveTip','#__LOCKRECORD__-tr','#__SUBMITBUTTONS__-tr','#__DELETEBUTTONS__-tr','#south','#edit-response-btn','#SurveyActionDropDown',
        '.dataEntryLeavePageBtn','.dataEntrySaveLeavePageBtn'
    );

    /**
     * redcap_every_page_top
     * Inject content for preview on Online Designer page
     */
    public function redcap_every_page_top($project_id) {
        global $Proj,$user_rights;
        if (!defined('PAGE') || PAGE!=='Design/online_designer.php' ) return;
        if (!defined('USERID')) return;
        if (!isset($_GET['page'])) return;

        $instrument = $this->escape($_GET['page']);
        $this->setMetadata($instrument);
        if (!array_key_exists($instrument, $Proj->forms)) return;

        $functionLabel = \RCView::tt('design_55').' ('.\RCView::tt('global_49').')';
        if (!isset($user_rights['forms'][$instrument]) || $user_rights['forms'][$instrument]=='0') {
            $enablePreview = 0;
            $btnPreviewInstrumentRecord = '<button id="PreviewInstrument_button" class="btn btn-xs btn-light fs13 disabled" disabled="disabled" style="pointer-events:auto;" title="'.\RCView::tt('config_05',false).'" href="javascript:;">'.$functionLabel.'</button>';
            $previewDialog = '';
        } else {
            $enablePreview = 1;
            $btnPreviewInstrumentRecord = '<button id="PreviewInstrument_button" class="btn btn-xs btn-light fs13" href="javascript:;">'.$functionLabel.'</button>';
            $previewDialog = $this->makeRecordEventInstanceSelectionDialog($instrument);
        }
        $this->initializeJavascriptModuleObject();
        ?>
        <!--Preview Instrument content, style and script-->
        <?=$btnPreviewInstrumentRecord?>
        <div id="PreviewInstrument_dialog">
            <?=$previewDialog?>
        </div>
        <div id="PreviewInstrument_display">
            <div id="PreviewInstrument_content"></div>
            <embed id="PreviewInstrument_embed" type="text/html"></embed>
        </div>
        <style type="text/css">
            #PreviewInstrument_button, #PreviewInstrument_dialog, #PreviewInstrument_display { display: none; }
        </style>
        <script type="text/javascript">
            let module = <?=$this->getJavascriptModuleObjectName()?>;
            module.enablePreview = <?=intval($enablePreview)?>;
            module.instrumentName = '<?=\js_escape($Proj->forms[$instrument]['menu'])?>';
            module.dialogTitle = '<?=\js_escape(\RCView::tt('design_55'))?>';
            module.dialogBtnPreview = '<?=\js_escape(\RCView::tt('design_699', false))?>';
            module.dialogBtnCancel = '<?=\js_escape(\RCView::tt('global_53', false))?>';
            module.dialogBtnClose = '<?=\js_escape(\RCView::tt('bottom_90', false))?>';
            module.dialogHeight = window.innerHeight * 0.9;
            module.dialogWidth = 900;
            module.dialogEmbedHeight = module.dialogHeight - 125;
            module.dialogEmbedWidth = module.dialogWidth - 50;
            module.instrument = '<?=\js_escape($instrument)?>';
            module.previewClick = function() {
                $('#PreviewInstrument_dialog').dialog({
                    width: 450,
                    title: module.dialogTitle,
                    buttons: [
                        {
                            text: module.dialogBtnCancel,
                            click: function() { $( this ).dialog( "close" ); }
                        },
                        {
                            text: module.dialogBtnPreview,
                            click: function() { 
                                let r = $('#PreviewInstrument_record').val();
                                let e = $('#PreviewInstrument_event').val();
                                let i = $('#PreviewInstrument_instance').val();
                                if (r=='' || e=='' || 1*e==0) {
                                    alert('Select preview context');
                                } else {
                                    $( this ).dialog( "close" ); 
                                    module.showPreview(r, e, i);
                                }
                            }
                        }
                    ]
                });
            };
            module.showPreview = function(rec, evt, i) {
                $('#PreviewInstrument_display').dialog({
                    width: module.dialogWidth,
                    height: module.dialogHeight,
                    title: module.dialogTitle+': <span style="color:#800000;font-weight:bold;">'+module.instrumentName+'</span>',
                    buttons: [
                        {
                            text: module.dialogBtnClose,
                            click: function() { $( this ).dialog( "close" ); }
                        }
                    ]
                });
                $('#PreviewInstrument_embed').attr('height', module.dialogEmbedHeight);
                $('#PreviewInstrument_embed').attr('width', module.dialogEmbedWidth);
                $('#PreviewInstrument_embed').attr('src', app_path_webroot+'DataEntry/index.php?pid='+pid+'&id='+rec+'&event_id='+evt+'&page='+module.instrument+'&instance='+i+'&em_preview_instrument=1');
//                $.get( 
//                    app_path_webroot+'DataEntry/index.php', 
//                    { pid: pid, id: rec, event_id: evt, page: module.instrument, instance: i, em_preview_instrument: 1 } 
//                ).done(function( data ) {
//                    $('#PreviewInstrument_content').html(data);
//                });
            };

            module.init = function() {
                if (module.enablePreview) {
                    $('#PreviewInstrument_button').on('click', module.previewClick);
                }
                $('#PreviewInstrument_button').appendTo($('#form_menu_description_label').parent('td').siblings(':last')).show();
            };

            $(document).ready(function(){
                module.init();
            });
        </script>
        <?php
    }
    
    /**
     * redcap_data_entry_form_top
     * Detect querystring parameter and hide page elements except #questiontable
     * If project is in Draft Mode use draft metadata rather than live 
     */
    public function redcap_data_entry_form_top($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {
        if (!isset($_GET['em_preview_instrument'])) return;
        if (!defined('USERID')) return;
        
        $this->setMetadata($instrument);
        
        \UIState::saveUIStateValue($project_id, 'PreviewInstrument_dialog', 'record', $record);
        \UIState::saveUIStateValue($project_id, 'PreviewInstrument_dialog', 'event_id', $event_id);
        \UIState::saveUIStateValue($project_id, 'PreviewInstrument_dialog', 'repeat_instance', $repeat_instance);
        ?>
        <!--Preview Instrument style and script-->
        <style type="text/css">
            <?=implode(',',static::$SuppressElements)?> {display: none !important;}
        </style>
        <script type="text/javascript">
            (() => {
                $('#center').siblings('div').remove();
                $('#form').siblings('div').remove();
                $('.dataEntryLeavePageBtn,.dataEntrySaveLeavePageBtn').remove();
                window.onbeforeunload = null;
            })();
        </script>
        <?php
    }

    /**
     * setMetadata($instrument)
     * If project is in draft mode, use metadata_temp as metadata for preview
     */
    protected function setMetadata($instrument) {
        global $Proj, $user_rights;
        if ($Proj->project['draft_mode']!='1') return;

        if (!array_key_exists($instrument, $Proj->forms)) {
            // new draft form, enable edit rights for preview
            $user_rights['forms'][$instrument] = '1';
        }

        $Proj->metadata = $Proj->metadata_temp;
        $Proj->forms[$instrument]['fields'] = array();
        foreach ($Proj->metadata_temp as $draftFieldName => $attrs) {
            if ($attrs['form_name']==$instrument) {
                if ($attrs['form_menu_description']!='') $Proj->forms[$instrument]['menu'] = $attrs['form_menu_description'];
                $Proj->forms[$instrument]['fields'][$draftFieldName] = $attrs['element_label'];
            }
        }
    }

    /**
     * makeRecordEventInstanceSelectionDialog($instrument)
     * Make HTML content for context selection dialog (record/event/instance)
     */
    protected function makeRecordEventInstanceSelectionDialog($instrument) {
        global $Proj,$project_id,$table_pk_label,$user_rights;
        $recordSelect = '';
        $eventSelect = '';
        $instanceSelect = '';
        $lastRecord = \UIState::getUIStateValue($project_id,'PreviewInstrument_dialog','record');
        $lastEvent = \UIState::getUIStateValue($project_id,'PreviewInstrument_dialog','event_id');
        $lastInstance = \UIState::getUIStateValue($project_id,'PreviewInstrument_dialog','repeat_instance');

        $num_records = \Records::getRecordCount(PROJECT_ID);
        if ($num_records > static::maxNumRecordsHideDropdowns) {
            $recordSelect = \RCView::input(array('id'=>'PreviewInstrument_record','type'=>'text','value'=>$lastRecord));
        } else {
            $study_id_array = \Records::getRecordList($project_id, $user_rights['group_id']);
            $extra_record_labels = \Records::getCustomRecordLabelsSecondaryFieldAllRecords(array_keys($study_id_array), true);
            $records = array(''=>'');
            foreach ($study_id_array as $recId) {
                $recLbl = $recId;
                if (is_array($extra_record_labels)) {
                    if (array_key_exists($recId, $extra_record_labels)) {
                        $recLbl .= " ".$extra_record_labels[$recId];
                    }
                }
                $records[$recId] = $recLbl;
            }
            $recordSelect = \RCView::select(array('id'=>'PreviewInstrument_record'), $records, $lastRecord);
        }

        $html = \RCView::div(
            array('class'=>'row my-1'),
            \RCView::div(array('class'=>'col-4'),$table_pk_label).
            \RCView::div(array('class'=>'col-8'),$recordSelect)
        );

        if (\REDCap::isLongitudinal()) {
            $formEvents = array(''=>'');
            foreach ($Proj->eventsForms as $eventId => $eventForms) {
                foreach ($eventForms as $form) {
                    if ($form == $instrument) {
                        $formEvents[$eventId] = $Proj->eventInfo[$eventId]['name_ext'];
                    }
                }
            }
            $eventSelect = \RCView::select(array('id'=>'PreviewInstrument_event'), $formEvents, $lastEvent);
            $html .= \RCView::div(
                array('class'=>'row my-1'),
                \RCView::div(array('class'=>'col-4'),\RCView::tt('global_141')).
                \RCView::div(array('class'=>'col-8'),$eventSelect)
            );
        } else {
            $html .= \RCView::input(array('id'=>'PreviewInstrument_event','type'=>'hidden','value'=>$Proj->firstEventId));
        }

        if ($Proj->isRepeatingFormAnyEvent($instrument)) {
            $instanceSelect = \RCView::input(array('id'=>'PreviewInstrument_instance','type'=>'text','value'=>$lastInstance));
            $html .= \RCView::div(
                array('class'=>'row my-1'),
                \RCView::div(array('class'=>'col-4'),\RCView::tt('data_entry_246')).
                \RCView::div(array('class'=>'col-8'),$instanceSelect)
            );
        } else {
            $html .= \RCView::input(array('id'=>'PreviewInstrument_instance','type'=>'hidden','value'=>''));
        }

        return "<div class='container'>$html</div>";
    }
}