<?php

declare(strict_types=1);

namespace App\Viewing\Value;

enum ViewDecisionReason: string
{
    case ActorTypeForcesJson = 'actor_type_forces_json';
    case RequestFormatJson = 'request_format_json';
    case PayloadFormatJson = 'payload_format_json';
    case ControlledHtmlRoute = 'view_controlled_html_route';
    case AcceptPrefersJson = 'accept_header_prefers_json';
    case XmlHttpRequestWithoutHtml = 'xml_http_request_without_html_preference';
    case UnknownActorForcesJson = 'unknown_actor_forces_json';
    case HtmlCandidateAllowed = 'html_candidate_allowed';
    case TemplateCandidateChainEmpty = 'template_candidate_chain_empty';
    case TemplateMissingFallback = 'template_missing_json_fallback';
    case TemplateLoaderFailed = 'template_loader_failed';
    case TemplateRenderFailed = 'template_render_failed';
}
