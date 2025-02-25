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
 *	@copyright		2007-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
namespace CeusMedia\REST\Server\Format;

use CeusMedia\Common\Net\HTTP\Response as HttpResponse;

/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_REST_Server_Format
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2023 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class XML extends AbstractFormat implements FormatInterface
{
	/** @var string $contentType */
	public string $contentType	= 'application/xhtml+xml';

	/** @var string $extension */
	public string $extension	= '.xml';

	public array $mimeTypes		= [
		'application/xml',
		'text/xml',
		'application/xhtml+xml'
	];

	/**
	 *	@param		HttpResponse							$response
	 *	@param		object|array|string|int|float|bool		$content
	 *	@return		string
	 */
	public function transform( HttpResponse $response, object|array|string|int|float|bool $content ): string
	{
		$response->addHeaderPair( 'Content-Type', $this->contentType );
		return serialize( $content );
	}
}
