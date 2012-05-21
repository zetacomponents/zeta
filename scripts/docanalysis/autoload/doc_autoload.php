<?php
/**
 * Autoloader definition for the Documentation component.
 *
 * @copyright Copyright (C) 2005-2007 eZ systems as. All rights reserved.
 * @license http://ez.no/licenses/new_bsd New BSD License
 * @version //autogentag//
 * @filesource
 * @package Documentation
 */

return array(
    'ezcDocException'                        => 'exceptions/doc.php',
    'ezcDocBlockTagTokenNotMatchedException' => 'exceptions/token_not_matched.php',
    'ezcDocInvalidDocBlockException'         => 'exceptions/invalid_doc_block.php',
    'ezcDocInvalidDocTagException'           => 'exceptions/invalid_tag.php',
    'ezcDocRuleNotApplyableException'        => 'exceptions/rule_not_applyable.php',
    'ezcDocUnrecognizedDocTagException'      => 'exceptions/unrecognized_tag.php',
    'ezcDocAnalysisElementGenerator'         => 'interfaces/element_analysis_generator.php',
    'ezcDocAnalysisReporter'                 => 'interfaces/analysis_reporter.php',
    'ezcDocAnalysisReporterOptions'          => 'options/reporter.php',
    'ezcDocAnalysisRule'                     => 'interfaces/analysis_rule.php',
    'ezcDocBlockBaseTag'                     => 'interfaces/doc_base_tag.php',
    'ezcDocBlockTag'                         => 'interfaces/doc_tag.php',
    'ezcDocBlockTagToken'                    => 'interfaces/token.php',
    'ezcDocAnalysisCache'                    => 'analysis/cache.php',
    'ezcDocAnalysisCheck'                    => 'check/check.php',
    'ezcDocAnalysisElement'                  => 'analysis/element.php',
    'ezcDocAnalysisMessage'                  => 'analysis/message.php',
    'ezcDocAnalysisPlainReporter'            => 'reporters/plain.php',
    'ezcDocAnalysisRuleHeadingAvailable'     => 'check/rules/header_available.php',
    'ezcDocAnalysisRuleParamCheck'           => 'check/rules/param_check.php',
    'ezcDocAnalysisRuleTagAvailable'         => 'check/rules/tag_available.php',
    'ezcDocAnalysisRuleTagsAvailable'        => 'check/rules/tags_available.php',
    'ezcDocAnalysisRuleFileHeaderCheck'      => 'check/rules/file_header.php',
    'ezcDocAnalysisRuleClassHeaderCheck'     => 'check/rules/class_header.php',
    'ezcDocBlock'                            => 'parser/block.php',
    'ezcDocBlockAccessTag'                   => 'parser/tags/access.php',
    'ezcDocBlockApichangeTag'                => 'parser/tags/apichange.php',
    'ezcDocBlockAuthorTag'                   => 'parser/tags/author.php',
    'ezcDocBlockCopyrightTag'                => 'parser/tags/copyright.php',
    'ezcDocBlockFilesourceTag'               => 'parser/tags/filesource.php',
    'ezcDocBlockIgnoreTag'                   => 'parser/tags/ignore.php',
    'ezcDocBlockInternalTag'                 => 'parser/tags/internal.php',
    'ezcDocBlockLicenseTag'                  => 'parser/tags/license.php',
    'ezcDocBlockLinkTag'                     => 'parser/tags/link.php',
    'ezcDocBlockMainclassTag'                => 'parser/tags/mainclass.php',
    'ezcDocBlockPackageTag'                  => 'parser/tags/package.php',
    'ezcDocBlockParamTag'                    => 'parser/tags/param.php',
    'ezcDocBlockParser'                      => 'parser/parser.php',
    'ezcDocBlockPropertyTag'                 => 'parser/tags/property.php',
    'ezcDocBlockReturnTag'                   => 'parser/tags/return.php',
    'ezcDocBlockSeeTag'                      => 'parser/tags/see.php',
    'ezcDocBlockSubsubpackageTag'            => 'parser/tags/subpackage.php',
    'ezcDocBlockTagCombinedToken'            => 'interfaces/combined_token.php',
    'ezcDocBlockTagDispatcher'               => 'parser/dispatcher.php',
    'ezcDocBlockTags'                        => 'parser/tags.php',
    'ezcDocBlockThrowsTag'                   => 'parser/tags/throws.php',
    'ezcDocBlockTodoTag'                     => 'parser/tags/todo.php',
    'ezcDocBlockVarTag'                      => 'parser/tags/var.php',
    'ezcDocBlockVersionTag'                  => 'parser/tags/version.php',
    'ezcDocClassAnalysisGenerator'           => 'analysis/generators/class.php',
    'ezcDocComponentAnalysisGenerator'       => 'analysis/generators/component.php',
    'ezcDocComponentReflection'              => 'reflections/component.php',
    'ezcDocFileAnalysisGenerator'            => 'analysis/generators/file.php',
    'ezcDocFileReflection'                   => 'reflections/file.php',
    'ezcDocMethodAnalysisGenerator'          => 'analysis/generators/method.php',
    'ezcDocPropertyAnalysisGenerator'        => 'analysis/generators/property.php',
);
?>
