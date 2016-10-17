<?php
/**
 *	...
 *
 *	Copyright (c) 2007-2016 Christian Würker (ceusmedia.de)
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
 *	@package		CeusMedia_REST
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
namespace CeusMedia\REST\Server;
/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_REST
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2007-2016 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/REST
 */
class Controller{

	protected function decoratePagination( $total, $limit, $page, $parameters = array() ){
		$path	= $this->resources->request->getPath();
		if( $limit && $limit < $total ){
			$lastPage	= ceil( $total / $limit );
			if( $page > 1 ){
				$args	= array_diff_key( $parameters, array( 'limit' => 1, 'page' => 1 ) );
				$args	= $args ? '?'.http_build_query( $parameters ) : '';
				$this->resources->response->addHeaderPair( 'Link', $path.$args.'; rel=FIRST' );
				if( $page < 3 )
					$this->resources->response->addHeaderPair( 'Link', $path.$args.'; rel=PREV' );
				else{
					$args	= array_merge( $parameters, array(
						'limit'	=> $limit,
						'page'	=> $page - 1,
					) );
					$args	= '?'.http_build_query( $args );
					$this->resources->response->addHeaderPair( 'Link', $path.$args.'; rel=PREV' );
				}
			}
			if( $page < $lastPage ){
				$args	= array_merge( $parameters, array(
					'limit'	=> $limit,
					'page'	=> $page + 1,
				) );
				$args	= '?'.http_build_query( $args );
				$this->resources->response->addHeaderPair( 'Link', $path.$args.'; rel=NEXT' );

				$args	= array_merge( $parameters, array(
					'limit'	=> $limit,
					'page'	=> $lastPage,
				) );
				$args	= '?'.http_build_query( $args );
				$this->resources->response->addHeaderPair( 'Link', $path.$args.'; rel=LAST' );
			}
		}
	}
}
