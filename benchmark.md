# Benchmark

This file contains a performance comparison of SCSS compilation runs across three PHP libraries:

- `bugo/dart-sass-compiler` (current project) - Pure PHP compiler for SCSS/Sass, compatible with modern Dart Sass
- [bugo/sass-embedded-php](https://github.com/dragomano/sass-embedded-php) - PHP wrapper for Dart Sass using a bridge between PHP and Node.js
- [scssphp/scssphp](https://github.com/scssphp/scssphp) - A well-known PHP library for SCSS compilation

## Test Environment

- **SCSS code**: Randomly generated, contains 200 classes with 4 nesting levels, variables, mixins and loops
- **OS**: Windows 11 25H2 (Build 10.0.26200.8037)
- **PHP version**: 8.5.4
- **Testing method**: Compilation via `compileString()` with execution time measurement

## Results

| Compiler | Time (sec) | CSS Size (KB) | Memory (MB) |
|------------|-------------|---------------|-------------|
| bugo/dart-sass-compiler | 0.3468 | 310.17 | 0.31 |
| bugo/sass-embedded-php | 0.1240 | 314.34 | 0.64 |
| scssphp/scssphp | 0.4855 | 311.35 | 22.28 |
