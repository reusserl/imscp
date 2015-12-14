<?php
/**
 * i-MSCP - internet Multi Server Control Panel
 * Copyright (C) 2010-2015 by Laurent Declercq <l.declercq@nuxwin.com>
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

namespace iMSCP\Core\Http\PhpEnvironment;

use Zend\Http\PhpEnvironment\Request as PhpEnvironmentRequest;

/**
 * Class Request
 *
 * Extends Zend\Http\PhpEnvironment\Request to add the ability to retrieve
 * the request content as a stream.
 *
 * @package iMSCP\Core\Http\PhpEnvironment
 */
class Request extends PhpEnvironmentRequest
{
    /**
     * @var string Stream URI or stream resource for content
     */
    protected $contentStream = 'php://input';

    /**
     * Return request content as a stream
     *
     * @return resource Stream
     */
    public function getContentAsStream()
    {
        if (is_resource($this->contentStream)) {
            rewind($this->contentStream);
            return $this->contentStream;
        }

        if (empty($this->content)) {
            return fopen($this->contentStream, 'r');
        }

        $this->contentStream = fopen('php://temp', 'r+');
        fwrite($this->contentStream, $this->content);
        rewind($this->contentStream);
        return $this->contentStream;
    }

    /**
     * Set the content stream to use with getContentAsStream()
     *
     * @param string|resource $stream Either the stream URI to use, or a stream resource
     * @return self
     */
    public function setContentStream($stream)
    {
        if (!is_string($stream) && !is_resource($stream)) {
            throw new \InvalidArgumentException('Invalid content stream');
        }

        $this->contentStream = $stream;
        return $this;
    }
}
