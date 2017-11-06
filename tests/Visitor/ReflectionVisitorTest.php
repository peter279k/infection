<?php
/**
 * Copyright © 2017 Maks Rafalko
 *  
 * License: https://opensource.org/licenses/BSD-3-Clause New BSD License
 */
declare(strict_types=1);

namespace Infection\Tests\Visitor;

use Infection\Visitor\FullyQualifiedClassNameVisitor;
use Infection\Visitor\ParentConnectorVisitor;
use Infection\Visitor\ReflectionVisitor;
use PhpParser\Lexer;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use PHPUnit\Framework\TestCase;

class ReflectionVisitorTest extends TestCase
{
    private $spyVisitor;

    protected function setUp()
    {
        $this->spyVisitor = $this->getInsideFunctionSpyVisitor();
    }

    public function test_it_detects_if_traversed_inside_class_method()
    {
        $code = file_get_contents(__DIR__ . '/../Files/Autoloaded/Reflection/rv-inside-class-method.php');

        $this->parseAndTraverse($code);

        $this->assertTrue($this->spyVisitor->isInsideFunction);
    }

    public function test_it_detects_if_traversed_inside_function()
    {
        $code = file_get_contents(__DIR__ . '/../Files/Autoloaded/Reflection/rv-inside-function.php');

        $this->parseAndTraverse($code);

        $this->assertTrue($this->spyVisitor->isInsideFunction);
    }

    public function test_it_detects_if_traversed_inside_closure()
    {
        $code = file_get_contents(__DIR__ . '/../Files/Autoloaded/Reflection/rv-inside-closure.php');

        $this->parseAndTraverse($code);

        $this->assertTrue($this->spyVisitor->isInsideFunction);
    }

    public function test_it_does_not_add_inside_function_flag_if_not_needed()
    {
        $code = file_get_contents(__DIR__ . '/../Files/Autoloaded/Reflection/rv-without-function.php');

        $this->parseAndTraverse($code);

        $this->assertFalse($this->spyVisitor->isInsideFunction);
    }

    private function getNodes(string $code) : array
    {
        $lexer = new Lexer();
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7, $lexer);

        return $parser->parse($code);
    }

    private function getInsideFunctionSpyVisitor()
    {
        return new class() extends NodeVisitorAbstract {
            public $isInsideFunction = false;

            public function enterNode(Node $node)
            {
                if ($node->hasAttribute(ReflectionVisitor::IS_INSIDE_FUNCTION_KEY)) {
                    $this->isInsideFunction = true;

                    return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                };
            }
        };
    }

    private function parseAndTraverse($code)
    {
        $nodes = $this->getNodes($code);

        $traverser = new NodeTraverser();

        $traverser->addVisitor(new ParentConnectorVisitor());
        $traverser->addVisitor(new FullyQualifiedClassNameVisitor());
        $traverser->addVisitor(new ReflectionVisitor());
        $traverser->addVisitor($this->spyVisitor);

        $traverser->traverse($nodes);
    }
}