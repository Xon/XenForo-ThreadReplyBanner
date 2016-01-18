<?php

class SV_ThreadReplyBanner_XenForo_ControllerPublic_Thread extends XFCP_SV_ThreadReplyBanner_XenForo_ControllerPublic_Thread
{
    public function actionEdit()
    {
        $response = parent::actionEdit();
        if ($response instanceof XenForo_ControllerResponse_View &&
            !empty($response->params['thread']) &&
            !empty($response->params['forum']))
        {
            $threadModel = $this->_getThreadModel();
            if ($threadModel->canManageThreadReplyBanner($response->params['thread'], $response->params['forum']))
            {
                $response->params['canEditThreadReplyBanner'] = true;
                $response->params['thread']['banner'] = $threadModel->getRawThreadReplyBanner($response->params['thread']['thread_id']);
            }
        }
        return $response;
    }

    public function actionSave()
    {
        SV_ThreadReplyBanner_Globals::$controller = $this;
        return parent::actionSave();
    }

    public function actionIndex()
    {
        $response = parent::actionIndex();

        if ($response instanceof XenForo_ControllerResponse_View && !empty($response->params['thread']))
        {
            $response->params['thread']['banner'] = $this->_getThreadModel()->getThreadReplyBanner($response->params['thread']);
        }
        return $response;
    }

    public function actionAddReply()
    {
        $response = parent::actionAddReply();
        if ($response instanceof XenForo_ControllerResponse_View && !empty($response->params['thread']))
        {
            $response->params['thread']['banner'] = $this->_getThreadModel()->getThreadReplyBanner($response->params['thread']);
        }
        return  $response;
    }

    public function actionReply()
    {
        $response = parent::actionReply();
        if ($response instanceof XenForo_ControllerResponse_View && !empty($response->params['thread']))
        {
            $response->params['thread']['banner'] = $this->_getThreadModel()->getThreadReplyBanner($response->params['thread']);
        }
        return $response;
    }
}