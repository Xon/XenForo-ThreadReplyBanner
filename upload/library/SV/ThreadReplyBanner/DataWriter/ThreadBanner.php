<?php


class SV_ThreadReplyBanner_DataWriter_ThreadBanner extends XenForo_DataWriter
{
    const OPTION_THREAD  = 'thread';
    const OPTION_LOG_EDIT = 'logEdit';
    const banner_length = 65536;
    //const banner_length = 16777215;

    protected function _getFields()
    {
        return [
            'xf_thread_banner' => [
                'thread_id'                => ['type' => self::TYPE_UINT, 'required' => true],
                'raw_text'                 => ['type' => self::TYPE_STRING, 'required' => true, 'verification' => ['$this', '_verifyBannerText']],
                'banner_state'             => ['type' => self::TYPE_BOOLEAN, 'default' => 1],
                'banner_user_id'           => ['type' => self::TYPE_UINT, 'default' => XenForo_Visitor::getUserId()],
                'banner_last_edit_date'    => ['type' => self::TYPE_UINT, 'default' => 0],
                'banner_last_edit_user_id' => ['type' => self::TYPE_UINT, 'default' => 0],
                'banner_edit_count'        => ['type' => self::TYPE_UINT_FORCED, 'default' => 0],
            ]
        ];
    }

    protected function _verifyBannerText(&$raw_text)
    {
        if (strlen($raw_text) > self::banner_length)
        {
            $this->error(new XenForo_Phrase('please_enter_value_using_x_characters_or_fewer', ['count' => self::banner_length]));
            return false;
        }

        return true;
    }

    protected function _getExistingData($data)
    {
        if (!$id = $this->_getExistingPrimaryKey($data, 'thread_id'))
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
        $defaultOptions[self::OPTION_THREAD] = null;

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

    protected function _getThread()
    {
        $thread = $this->getOption(self::OPTION_THREAD);
        if (empty($thread))
        {
            $thread = $this->_getThreadModel()->getThreadById($this->get('thread_id'));
            $this->setOption(self::OPTION_THREAD, $thread);
        }

        return $thread;
    }

    protected function _postSave()
    {
        if ($this->isUpdate() && $this->isChanged('raw_text') && $this->getOption(self::OPTION_LOG_EDIT))
        {
            $this->_insertEditHistory();
        }

        $this->_db->query(
            "UPDATE xf_thread
            SET has_banner = ?
            WHERE thread_id = ?",
            [$this->get('banner_state'), $this->get('thread_id')]
        );

        $thread = $this->_getThread();
        if ($this->isChanged('raw_text'))
        {
            XenForo_Model_Log::logModeratorAction('thread', $thread, 'replybanner', ['banner' => $this->get('raw_text')]);
        }
        if ($this->isChanged('banner_state') && !$this->get('banner_state'))
        {
            XenForo_Model_Log::logModeratorAction('thread', $thread, 'replybanner_deleted');
        }

        $this->_getThreadModel()->updateThreadBannerCache($this->get('thread_id'), $this->getMergedData());
    }

    protected function _postDelete()
    {
        $this->_db->query(
            "UPDATE xf_thread
            SET has_banner = 0
            WHERE thread_id = ?",
            [$this->get('thread_id')]
        );

        $thread = $this->_getThread();
        XenForo_Model_Log::logModeratorAction('thread', $thread, 'replybanner_deleted');
        $this->_getThreadModel()->updateThreadBannerCache($this->get('thread_id'), null);
    }

    protected function _insertEditHistory()
    {
        /** @var XenForo_DataWriter_EditHistory $historyDw */
        $historyDw = XenForo_DataWriter::create('XenForo_DataWriter_EditHistory', XenForo_DataWriter::ERROR_SILENT);
        $historyDw->bulkSet(
            [
                'content_type' => 'thread_banner',
                'content_id'   => $this->get('thread_id'),
                'edit_user_id' => XenForo_Visitor::getUserId(),
                'old_text'     => $this->getExisting('raw_text')
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
