# Benchmark

This file contains a performance comparison of SCSS compilation runs across three PHP libraries:

- `bugo/dart-sass-compiler` (current project) - Pure PHP compiler for SCSS/Sass, compatible with modern Dart Sass
- [bugo/sass-embedded-php](https://github.com/dragomano/sass-embedded-php) - PHP wrapper for Dart Sass using a bridge between PHP and Node.js
- [scssphp/scssphp](https://github.com/scssphp/scssphp) - A well-known PHP library for SCSS compilation

## Test Environment

- **SCSS code**: Randomly generated, contains 200 classes with 4 nesting levels, variables, mixins and loops
- **OS**: Windows 11 25H2 (Build 10.0.26200.7705)
- **PHP version**: 8.5.1
- **Testing method**: Compilation via `compileString()` with execution time measurement

## Results

| Compiler | Time (sec) | CSS Size (KB) | Memory (MB) |
|------------|-------------|---------------|-------------|
| bugo/dart-sass-compiler | 0.3520 | 299.50 | 6.88 |
| bugo/sass-embedded-php | 0.6252 | 303.67 | 0.30 |
| scssphp/scssphp | 0.5532 | 300.68 | 22.99 |

*Note: These results are approximate. Run `composer run benchmark` from the project root to see the actual results.*
