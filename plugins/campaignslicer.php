<?php

class campaignslicer extends phplistPlugin {
    public $name = "Send to a subset of the total campaign";
    public $coderoot = '';
    public $version = "0.1";
    public $authors = 'Michiel Dethmers';
    public $enabled = 1;
    public $description = 'Send to a maximum of subscribers in a campaign, instead of all';

    private $actions = array(
        'suspend' => 'Suspend',
        'marksent' => 'Mark as sent',
    );

    public function sendMessageTab($messageId = 0, $messageData = array()) {

        $actionHTML = '<select name="campaignslicer_action">';
        foreach ($this->actions as $key => $val) {
            $actionHTML .= sprintf('<option value="%s" ',$key);
            if ($messageData['campaignslicer_action'] == $key) {
                $actionHTML .= 'selected="selected" ';
            }
            $actionHTML .= '>'.s($val).'</option>';
        }
        $actionHTML .= '</select>';

        $html = sprintf('
            <table>
            <tr>
                <td>'.s('Maximum subscribers to send').'<br/>'.s('Use 0 to send to all').'</td>
                <td><input type="text" name="%s_max" value="%d" /></td>
            </tr>
            <tr>
                <td>'.s('Action when maximum reached').'</td>

                <td>%s</td>
            </tr>
            </table>','campaignslicer',$messageData['campaignslicer_max'],$actionHTML);

        return $html;
    }

    public function sendMessageTabTitle($messageid = 0)
    {
        return s('Slice');
    }

    public function throttleSend($messageData, $userData)
    {
        $max = (int) $messageData['campaignslicer_max'];
        if ($max <= 0) return false;

        $totalsent = (int) $messageData['astext'] +
            $messageData['ashtml'] +
            $messageData['astextandhtml'] +
            $messageData['aspdf'] +
            $messageData['astextandpdf'] +
            $messageData['counters']['sent_users_for_message '.$messageData['id']];

        if ($totalsent >= $messageData['campaignslicer_max']) {
            switch ($messageData['campaignslicer_action']) {
                case 'marksent':
                    Sql_query(sprintf('update %s set status = "sent" where id = %d',$GLOBALS['tables']['message'], $messageData['id']));
                    logEvent(s('Campaign %d finished by campaign slicer plugin, MAX of %d reached', $messageData['id'],$messageData['campaignslicer_max']));
                    break;
                case 'suspend':
                default:
                    Sql_query(sprintf('update %s set status = "suspended" where id = %d',$GLOBALS['tables']['message'], $messageData['id']));
                    logEvent(s('Campaign %d suspended by campaign slicer plugin, MAX of %d reached', $messageData['id'],$messageData['campaignslicer_max']));
                    break;
            }

            return true;
        }
        return false;
    }

    public function dependencyCheck() {
        global $plugins;
        return array(
            'phpList version 3.2.4 or later' => version_compare(VERSION, '3.2.3') > 0,
        );
    }
}
