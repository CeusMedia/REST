<?php
use CeusMedia\Common\Net\HTTP\Response as HttpResponse;
use CeusMedia\REST\Server\Format\FormatInterface;
use CeusMedia\REST\Server\Format\JSON as JsonFormat;
use PHPUnit\Framework\TestCase;

class JSONTest extends TestCase
{
	protected FormatInterface $format;
	protected HttpResponse $response;

	public function setUp(): void
	{
		$this->format	= new JsonFormat();
		$this->response	= new HttpResponse();
	}

	public function testGetExtension(): void
	{
		self::assertEquals( '.json', $this->format->getExtension() );
	}

	public function testGetContentType(): void
	{
		self::assertEquals( 'application/json', $this->format->getContentType() );
	}

	public function testGetMimeTypes(): void
	{
		self::assertEquals( ['application/json'], $this->format->getMimeTypes() );
	}

	public function testTransform(): void
	{
		self::assertEquals( 1, $this->format->transform( $this->response, 1 ) );
		self::assertEquals( '"1"', $this->format->transform( $this->response, '1' ) );
		self::assertEquals( "[\n    1,\n    2,\n    3\n]", $this->format->transform( $this->response, [1, 2, 3] ) );
	}
}
