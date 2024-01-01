<?php

declare(strict_types=1);

/**
 * @package     Triangle MongoDB Plugin
 * @link        https://github.com/Triangle-org/MongoDB
 *
 * @author      Ivan Zorin <creator@localzet.com>
 * @copyright   Copyright (c) 2018-2024 Localzet Group
 * @license     GNU Affero General Public License, version 3
 *
 *              This program is free software: you can redistribute it and/or modify
 *              it under the terms of the GNU Affero General Public License as
 *              published by the Free Software Foundation, either version 3 of the
 *              License, or (at your option) any later version.
 *
 *              This program is distributed in the hope that it will be useful,
 *              but WITHOUT ANY WARRANTY; without even the implied warranty of
 *              MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *              GNU Affero General Public License for more details.
 *
 *              You should have received a copy of the GNU Affero General Public License
 *              along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

namespace Triangle\MongoDB\Queue;

use DateTime;
use Illuminate\Queue\Jobs\DatabaseJob;

/**
 *
 */
class MongoJob extends DatabaseJob
{
    /**
     * Indicates if the job has been reserved.
     * @return bool
     */
    public function isReserved()
    {
        return $this->job->reserved;
    }

    /**
     * @return DateTime
     */
    public function reservedAt()
    {
        return $this->job->reserved_at;
    }
}
