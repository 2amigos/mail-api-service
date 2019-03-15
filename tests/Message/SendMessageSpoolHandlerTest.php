<?php

namespace App\Application\Message;

use App\Infrastructure\Mustache\Helpers\TranslatorHelper;
use League\Flysystem\Filesystem;
use Mustache_Context;
use PHPUnit\Framework\TestCase;

class SendEmailHandlerTest extends TestCase
{
    private $mailer;
    private $mustache;

    public function setUp()
    {
        $this->mailer = $this->getMockBuilder(\Swift_Mailer::class)
            ->setMethods(['send'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->mailer->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $this->mustache = $this->getMockBuilder(\Mustache_Engine::class)
            ->setMethods(['loadTemplate', 'render'])
            ->disableOriginalConstructor()
            ->getMock();



        $template = $this->getMockBuilder(Mustache_Test_TemplateStub::class)
            ->setMethods(['render'])
            ->disableOriginalConstructor()
            ->getMock();

        $template->expects($this->exactly(2))
            ->method('render')
            ->withAnyParameters()
            ->willReturn('Hello World');

        $this->mustache->expects($this->exactly(2))
            ->method('loadTemplate')
            ->withAnyParameters()
            ->willReturn($template);

    }

    public function testShouldReturnTrue(): void
    {
        $translator = new TranslatorHelper('');
        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $command = new SendMessageCommand(
            [
                'language' => null,
                'template' => 'template-name',
                'from' => 'from@from.com',
                'to' => 'to@to.com',
                'subject' => 'subject',
                'data' => ['a' => 'b'],
            ],
            []
        );

        $data = (new SendMessageSpoolHandler($this->mailer, $this->mustache, $translator, $filesystem))
            ->handle($command);

        $this->assertEquals(true, $data['success']);

    }

    public function testShouldBeAbleToWorkWithLanguages(): void
    {
        $translator = $this->getMockBuilder(TranslatorHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['setLanguage', 'get'])
            ->disableOriginalConstructor()
            ->getMock();

        $translator->expects($this->once())
            ->method('setLanguage')
            ->with('es')
            ->willReturn(null);

        $translator->expects($this->once())
            ->method('get')
            ->with('subject')
            ->willReturn('Asunto');

        $filesystem = $this->getMockBuilder(Filesystem::class)
            ->disableOriginalConstructor()
            ->getMock();

        $command = new SendMessageCommand(
            [
                'language' => 'es',
                'template' => 'template-name',
                'from' => 'from@from.com',
                'to' => 'to@to.com',
                'subject' => 'subject',
                'data' => ['a' => 'b'],
            ],
            []
        );

        $data = (new SendMessageSpoolHandler($this->mailer, $this->mustache, $translator, $filesystem))
            ->handle($command);

        $this->assertEquals(true, $data['success']);
    }

}

class Mustache_Test_TemplateStub extends \Mustache_Template
{
    public $rendered;

    public function getMustache()
    {
        return $this->mustache;
    }

    public function renderInternal(Mustache_Context $context, $indent = '', $escape = false)
    {
        return $this->rendered;
    }

}
