<?php

/**
 *    Copyright (C) 2016 EURO-LOG AG
*
*    All rights reserved.
*
*    Redistribution and use in source and binary forms, with or without
*    modification, are permitted provided that the following conditions are met:
*
*    1. Redistributions of source code must retain the above copyright notice,
*       this list of conditions and the following disclaimer.
*
*    2. Redistributions in binary form must reproduce the above copyright
*       notice, this list of conditions and the following disclaimer in the
*       documentation and/or other materials provided with the distribution.
*
*    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
*    INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
*    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
*    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
*    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
  *    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
  *    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
*    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
*    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
*    POSSIBILITY OF SUCH DAMAGE.
*
*/

namespace OPNsense\Monit;

use OPNsense\Base\BaseModel;

/**
 * Class Monit
 * @package OPNsense\Monit
 */
class Monit extends BaseModel
{
    /**
     * @var resource|null holds the file handle for the lock file
     */
    private $internalLockHandle = null;

    /**
     * lock the model to avoid issues with concurrent access
     * @throws \Exception
     */
    public function __construct($lock = true)
    {
        if ($lock == true) {
            $this->requestLock();
            $this->internalLockHandle = fopen("/tmp/monit.lock", "w+");
            if (is_resource($this->internalLockHandle) == false || flock($this->internalLockHandle, LOCK_EX) == false) {
                throw new \Exception("Cannot lock monit model");
            }
        }
        parent::__construct();
    }

    /**
     * release lock after usage
     */
    public function __destruct()
    {
        $this->releaseLock();
    }

    /**
     * lock the model
     */
    public function requestLock()
    {
        $this->internalLockHandle = fopen("/tmp/monit.lock", "w+");
        if (is_resource($this->internalLockHandle) == false || flock($this->internalLockHandle, LOCK_EX) == false) {
            throw new \Exception("Cannot lock monit model");
        }
    }
    /**
     * release lock
     */
    public function releaseLock()
    {
        if (is_resource($this->internalLockHandle)) {
            flock($this->internalLockHandle, LOCK_UN);
            fclose($this->internalLockHandle);
        }
    }

    /**
     * get configuration state
     * @return bool
     */
    public function configChanged()
    {
        return file_exists("/tmp/monit.dirty");
    }

    /**
     * mark configuration as changed
     * @return bool
     */
    public function configDirty()
    {
        return @touch("/tmp/monit.dirty");
    }

    /**
     * mark configuration as consistent with the running config
     * @return bool
     */
    public function configClean()
    {
        return @unlink("/tmp/monit.dirty");
    }
}