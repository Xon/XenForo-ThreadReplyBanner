<?php


class SV_ThreadReplyBanner_DataWriter_ThreadBanner extends XenForo_DataWriter
{
    const OPTION_LOG_EDIT = 'logEdit';

    protected function _getFields()
    {
        return [
            'xf_thread_banner' => [
                'thread_id'                => ['type' => self::TYPE_UINT, 'required' => true],
                'raw_text'                 => ['type' => self::TYPE_STRING, 'required' => true, 'max' => 16777215],
                'banner_user_id'           => ['type' => self::TYPE_UINT, 'default' => XenForo_Visitor::getUserId()],
                'banner_last_edit_date'    => ['type' => self::TYPE_UINT, 'default' => 0],
                'banner_last_edit_user_id' => ['type' => self::TYPE_UINT, 'default' => 0],
                'banner_edit_count'        => ['type' => self::TYPE_UINT_FORCED, 'default' => 0],
            ]
        ];
    }

    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data,'thread_id'))
        {
            return false;
        }

        return ['xf_thread_banner' => $this->_getThreadModel()->getRawThreadReplyBanner($id)];
    }

    protected function _getUpdateCondition($tableName)
    {
        return 'thread_id = ' . $this->_db->quote($this->getExisting('thread_id'));
    }

    protected function _getDefaultOptions()
    {
        $defaultOptions = parent::_getDefaultOptions();
        $editHistory = XenForo_Application::getOptions()->editHistory;
        $defaultOptions[self::OPTION_LOG_EDIT] = empty($editHistory['enabled']) ? false : $editHistory['enabled'];

        return $defaultOptions;
    }

    protected function _preSave()
    {
        if ($this->isUpdate() && $this->isChanged('raw_text'))
        {
            if (!$this->isChanged('banner_last_edit_date'))
            {
                $this->set('banner_last_edit_date', XenForo_Application::$time);
                if (!$this->isChanged('banner_last_edit_user_id'))
                {
                    $this->set('banner_last_edit_user_id', XenForo_Visitor::getUserId());
                }
            }

            if (!$this->isChanged('banner_edit_count'))
            {
                $this->set('banner_edit_count', $this->get('banner_edit_count') + 1);
            }
        }
        if ($this->isChanged('banner_edit_count') && $this->get('banner_edit_count') == 0)
        {
            $this->set('banner_last_edit_date', 0);
        }
        if (!$this->get('banner_last_edit_date'))
        {
            $this->set('banner_last_edit_user_id', 0);
        }
    }

    protected function _postSave()
    {
        if ($this->isUpdate() && $this->isChanged('raw_text') && $this->getOption(self::OPTION_LOG_EDIT))
        {
            $this->_insertEditHistory();
        }

        $this->_db->query(
            "UPDATE xf_thread
            SET has_banner = 1
            WHERE thread_id = ?",
            [$this->get('thread_id')]
        );
    }

    protected function _postDelete()
    {
        $this->_db->query(
            "UPDATE xf_thread
            SET has_banner = 0
            WHERE thread_id = ?",
            [$this->get('thread_id')]
        );
    }

    protected function _insertEditHistory()
    {
        /** @var XenForo_DataWriter_EditHistory $historyDw */
        $historyDw = XenForo_DataWriter::create('XenForo_DataWriter_EditHistory', XenForo_DataWriter::ERROR_SILENT);
        $historyDw->bulkSet(
            [
                'content_type' => 'thread_banner',
                'content_id'   => $this->get('message_id'),
                'edit_user_id' => XenForo_Visitor::getUserId(),
                'old_text'     => $this->getExisting('message')
            ]
        );
        $historyDw->save();
    }

    /**
     * @return XenForo_Model|XenForo_Model_Thread|SV_ThreadReplyBanner_XenForo_Model_Thread
     */
    protected function _getThreadModel()
    {
        return $this->getModelFromCache('XenForo_Model_Thread');
    }
}
