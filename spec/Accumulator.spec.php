<?php

namespace Kavinsky\CloverMerge\Spec;

use Kavinsky\CloverMerge\Accumulator;
use Kavinsky\CloverMerge\File;
use Kavinsky\CloverMerge\Utilities;

/**
 * @phan-closure-scope \Kahlan\Scope
 */
describe('Accumulator', function () {
    describe('parseAll', function () {
        context('Receives a vector of nice XML documents and merges in "inclusive" mode.', function () {
            beforeEach(function () {
                $this->accumulator = new Accumulator('inclusive');
                $this->accumulator->parseAll(new \Ds\Vector([
                    simplexml_load_file(__DIR__.'/fixtures/file-with-package.xml'),
                    simplexml_load_file(__DIR__.'/fixtures/file-without-package.xml')
                ]));
            });

            it('returns a map of files parsed from the input.', function () {
                $result = $this->accumulator->getFiles();

                expect($result)->toBeAnInstanceOf(\Ds\Map::class);
                expect($result)->toHaveLength(1);

                expect($result->keys()->toArray())->toBe([
                    'test.php'
                ]);

                $file = $result->get('test.php');
                expect($file)->toBeAnInstanceOf(File::class);

                $lines = $file->getLines();
                expect($lines)->toHaveLength(5);

                expect($lines->keys()->toArray())->toBe([1, 2, 3, 4, 5]);

                expect($lines->get(1)->getCount())->toBe(0);
                expect($lines->get(2)->getCount())->toBe(2);
                expect($lines->get(3)->getCount())->toBe(4);
                expect($lines->get(4)->getCount())->toBe(6);
                expect($lines->get(5)->getCount())->toBe(8);
            });
        });
        context('Receives a vector of nice XML documents and merges in "exclusive" mode.', function () {
            beforeEach(function () {
                $this->accumulator = new Accumulator('exclusive');
                $this->accumulator->parseAll(new \Ds\Vector([
                    simplexml_load_file(__DIR__.'/fixtures/file-with-package.xml'),
                    simplexml_load_file(__DIR__.'/fixtures/file-with-differences.xml')
                ]));
            });

            it('returns a map of files parsed from the input.', function () {
                $result = $this->accumulator->getFiles();

                expect($result)->toBeAnInstanceOf(\Ds\Map::class);
                expect($result)->toHaveLength(1);

                expect($result->keys()->toArray())->toBe([
                    'test.php'
                ]);

                $file = $result->get('test.php');
                expect($file)->toBeAnInstanceOf(File::class);

                $lines = $file->getLines();
                expect($lines)->toHaveLength(2);

                expect($lines->keys()->toArray())->toBe([2, 4]);

                expect($lines->get(2)->getCount())->toBe(2);
                expect($lines->get(4)->getCount())->toBe(6);
            });
        });
        context('Receives a vector of nice XML documents and merges in "additive" mode.', function () {
            beforeEach(function () {
                $this->accumulator = new Accumulator('additive');
                $this->accumulator->parseAll(new \Ds\Vector([
                    simplexml_load_file(__DIR__.'/fixtures/file-with-package.xml'),
                    simplexml_load_file(__DIR__.'/fixtures/file-with-differences.xml')
                ]));
            });

            it('returns a map of files parsed from the input.', function () {
                $result = $this->accumulator->getFiles();

                expect($result)->toBeAnInstanceOf(\Ds\Map::class);
                expect($result)->toHaveLength(1);

                expect($result->keys()->toArray())->toBe([
                    'test.php'
                ]);

                $file = $result->get('test.php');
                expect($file)->toBeAnInstanceOf(File::class);

                $lines = $file->getLines();
                expect($lines)->toHaveLength(5);

                expect($lines->keys()->toArray())->toBe([1, 2, 3, 4, 5]);

                expect($lines->get(1)->getCount())->toBe(0);
                expect($lines->get(2)->getCount())->toBe(2);
                expect($lines->get(3)->getCount())->toBe(2);
                expect($lines->get(4)->getCount())->toBe(6);
                expect($lines->get(5)->getCount())->toBe(4);
            });
        });
        context('Receives a vector of XML documents with junk included.', function () {
            beforeEach(function () {
                allow(Utilities::class)->toReceive('::logWarning')->andReturn();
                $this->accumulator = new Accumulator('inclusive');
                $this->accumulator->parseAll(new \Ds\Vector([
                    simplexml_load_file(__DIR__.'/fixtures/file-with-junk.xml'),
                    simplexml_load_file(__DIR__.'/fixtures/non-clover.xml')
                ]));
            });

            /** @phan-suppress PhanUndeclaredProperty */
            it('ignores the junk and returns a map of files parsed from the input.', function () {
                $result = $this->accumulator->getFiles();

                expect(Utilities::class)->toReceive('::logWarning')->with('Ignoring unexpected element: bogus.');
                expect(Utilities::class)->toReceive('::logWarning')->with('Ignoring unexpected element: dinosaurs.');
                expect(Utilities::class)->toReceive('::logWarning')->with('Ignoring unexpected element: folder.');

                expect($result)->toBeAnInstanceOf('\Ds\Map');
                expect($result)->toHaveLength(1);

                $file = $result->first()->value;
                expect($file)->toBeAnInstanceOf(File::class);

                $lines = $file->getLines();
                expect($lines)->toHaveLength(5);

                $keys = $lines->keys();
                expect($keys->toArray())->toBe([1, 2, 3, 4, 5]);

                expect($lines->get(1)->getCount())->toBe(0);
                expect($lines->get(2)->getCount())->toBe(1);
                expect($lines->get(3)->getCount())->toBe(2);
                expect($lines->get(4)->getCount())->toBe(3);
                expect($lines->get(5)->getCount())->toBe(4);
            });
        });
        context('Receives a vector of XML documents with errors.', function () {
            beforeEach(function () {
                allow(Utilities::class)->toReceive('::logWarning')->andReturn();
                $this->accumulator = new Accumulator('inclusive');
                $this->accumulator->parseAll(new \Ds\Vector([
                    simplexml_load_file(__DIR__.'/fixtures/file-with-errors.xml')
                ]));
            });

            it('Makes a warning log entry for each encountered error.', function () {
                expect(Utilities::class)->toReceive('::logWarning')->with('Ignoring package with no name.')->times(2);
                expect(Utilities::class)->toReceive('::logWarning')->with('Ignoring file with no name.');
                expect(Utilities::class)->toReceive('::logWarning')->with('Ignoring unexpected element: bogus.');
            });
        });
    });
});
