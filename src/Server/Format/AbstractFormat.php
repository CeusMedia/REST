<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

/**
 *	...
 *
 *	Copyright (c) 2007-2023 Christian Würker (ceusmedia.de)
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_REST_Server_Format
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
namespace CeusMedia\REST\Server\Format;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_REST_Server_Format
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */


abstract class AbstractFormat
{
	public string $contentType	= '';

	public string $extension	= '';

	public array $mimeTypes		= [];

	/**
	 *	@return		string
	 */
	public function getContentType(): string
	{
		return $this->contentType;
	}

	/**
	 *	@return		string
	 */
	public function getExtension(): string
	{
		return $this->extension;
	}

	/**
	 *	@return		string[]
	 */
	public function getMimeTypes(): array
	{
		return $this->mimeTypes;
	}
}