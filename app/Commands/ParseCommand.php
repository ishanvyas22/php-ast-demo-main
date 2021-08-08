<?php

namespace App\Commands;

use Illuminate\Support\Facades\Storage;
use LaravelZero\Framework\Commands\Command;
use PhpParser\{Error, ParserFactory, Node, NodeFinder, NodeTraverser, NodeVisitorAbstract, NodeDumper};
use PhpParser\NodeVisitor\ParentConnectingVisitor;

class ParseCommand extends Command
{
    /**
     * The signature of the command.
     *
     * @var string
     */
    protected $signature = 'parse
                            {file : Path to file (required)}';

    /**
     * The description of the command.
     *
     * @var string
     */
    protected $description = 'Parse PHP code via parser';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $analysisErrors = [];
        $code = Storage::disk('stubs')->get($this->argument('file'));
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

        try {
            $traverser = new NodeTraverser;
            $traverser->addVisitor(new ParentConnectingVisitor);

            $ast = $parser->parse($code);
            $ast = $traverser->traverse($ast);

            $assignedVariables = [];
            foreach ($ast as $node) {
                // Store variables in temporary variable
                if (isset($node->expr) && $node->expr instanceof Node\Expr\Assign) {
                    $assignedVariables[$node->expr->var->name] = $node->expr->expr;
                }

                // Rule - Undefined variable: Checks for undefined variables in binary operation
                if (isset($node->expr) && $node->expr instanceof Node\Expr\Assign && isset($node->expr->expr) && $node->expr->expr instanceof Node\Expr\BinaryOp\Plus) {
                    if ($node->expr->expr->left instanceof Node\Expr\Variable) {
                        $varName = $node->expr->expr->left->name;

                        if (! isset($assignedVariables[$varName])) {
                            $this->error(sprintf('Undefined variable: $%s', $varName));
                            $analysisErrors[] = sprintf('Undefined variable: $%s', $varName);
                        }
                    }

                    if ($node->expr->expr->right instanceof Node\Expr\Variable) {
                        $varName = $node->expr->expr->right->name;

                        if (! isset($assignedVariables[$varName])) {
                            $this->error(sprintf('Undefined variable: $%s', $varName));
                            $analysisErrors[] = sprintf('Undefined variable: $%s', $varName);
                        }
                    }
                }

                // Rule - Undefined variable: Checks for undefined variables when echoing any variabl
                if ($node instanceof Node\Stmt\Echo_) {
                    foreach ($node->exprs as $expr) {
                        if ($expr instanceof Node\Expr\Variable) {
                            if (! isset($assignedVariables[$expr->name])) {
                                $this->error(sprintf('Undefined variable: $%s', $expr->name));
                                $analysisErrors[] = sprintf('Undefined variable: $%s', $varName);
                            }
                        }
                    }
                }
            }
        } catch (Error $e) {
            $this->error($e->getMessage());
        }

        if (empty($analysisErrors)) {
            $this->info('No errors found!');
        }
    }
}
