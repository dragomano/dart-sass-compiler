# Benchmark

This file contains a performance comparison of SCSS compilation runs across three PHP libraries:

- [scssphp/scssphp](https://github.com/scssphp/scssphp) - A well-known PHP library for SCSS compilation
- [bugo/sass-embedded-php](https://github.com/dragomano/sass-embedded-php) - PHP wrapper for Dart Sass using a bridge between PHP and Node.js
- `bugo/dart-sass-compiler` (current project) - Pure PHP compiler for SCSS/Sass, compatible with modern Dart Sass

## Test Environment

- **SCSS code**: Randomly generated, contains 200 classes with 4 nesting levels, variables, mixins and loops ([link](generated.scss))
- **OS**: Windows 11 24H2 (Build 10.0.26100.7019)
- **PHP version**: 8.2.30
- **Testing method**: Compilation via `compileString()` with execution time measurement

## Results

| Compiler                | Time (sec) | CSS Size (KB) | Memory (MB) |
|-------------------------|------------|---------------|-------------|
| scssphp/scssphp         | 2.2613     | 300.73        | 0.66        |
| bugo/sass-embedded-php  | 0.6020     | 363.33        | 0.36        |
| bugo/dart-sass-compiler | 2.0638     | 299.83        | 0.29        |

*Note: These results are approximate. Run `php benchmark.php` from the project root to see the actual results.*
