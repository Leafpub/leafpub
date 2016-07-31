# Contributing to Postleaf

Thanks for your help in making Postleaf better. Before you submit an issue or pull request, please read through this document.

Following these guidelines helps to communicate that you respect the time of the developers managing and developing this open source project. In return, they should reciprocate that respect in addressing your issue or assessing patches and features.

## Using the issue tracker

[The issue tracker](https://github.com/postleaf/postleaf/issues) is the preferred channel for [bug reports](#bug-reports) and [submitting pull requests](#pull-requests), but please respect the following restrictions:

* Please **do not** use the issue tracker for personal support requests. [The Postleaf Community Forum](https://community.postleaf.org/) is a better place to get help.

* Please **do not** derail or troll issues. Keep the discussion on topic and respect the opinions of others.

* Please **do not** post comments consisting solely of "+1" or ":thumbsup:". Use [GitHub's "reactions" feature](https://github.com/blog/2119-add-reactions-to-pull-requests-issues-and-comments) instead.

* Please **do not** open issues or pull requests regarding bugs in third party libraries. Open them in their respective repositories.

## Bug reports

A bug is a _demonstrable problem_ that is caused by the code in the repository. Good bug reports are extremely helpful, so thanks!

Guidelines for bug reports:

1. **Validate and lint your code** &mdash; to ensure your problem isn't caused by a simple error in your own code.

2. **Use the GitHub issue search** &mdash; check if the issue has already been reported.

3. **Check if the issue has been fixed** &mdash; try to reproduce it using the latest `master` or development branch in the repository.

4. **Isolate the problem** &mdash; ideally create a [reduced test case](https://css-tricks.com/reduced-test-cases/) and a live example.

A good bug report shouldn't leave others needing to chase you up for more information. Please try to be as detailed as possible in your report. What is your environment? What steps will reproduce the issue? What browser(s) and OS experience the problem? Do other browsers show the bug differently? What would you expect to be the outcome? All these details will help people to fix any potential bugs.

Example:

> Short and descriptive example bug report title
>
> A summary of the issue and the browser/OS environment in which it occurs. If
> suitable, include the steps required to reproduce the bug.
>
> 1. This is the first step
> 2. This is the second step
> 3. Further steps, etc.
>
> `<url>` - a link to the reduced test case
>
> Any other information you want to share that is relevant to the issue being
> reported. This might include the lines of code that you have identified as
> causing the bug, and potential solutions (and your opinions on their
> merits).

## Pull requests

Good pull requests — patches, improvements, new features — are a fantastic help. They should remain focused in scope and avoid containing unrelated commits.

**Please ask first** before embarking on any significant pull request (e.g. implementing features, refactoring code, porting to a different language), otherwise you risk spending a lot of time working on something that the project's developers might not want to merge into the project.

Please adhere to the [coding guidelines](#code-guidelines) used throughout the project (indentation, accurate comments, etc.) and any other requirements.

**Do not edit CSS and JS files in `app/src/admin`!** Those files are automatically generated. You should edit the source files in `scripts` and `styles` instead.

Adhering to the following process is the best way to get your work included in the project:

## Code guidelines

We try to adhere as closely as possible to the following code guidelines, and we ask that you do the same. If you follow these basic rules and pay attention to the existing code, you'll be just fine.

When in doubt, try to stay consistent with the rest of the code base and keep it practical. If you feel there's something missing from these guidelines, let the developers know or submit a PR so we can address it.

### HTML

We generally follow [@mdo's code guide](http://codeguide.co/#html) for HTML, with the exception of four spaces instead of two.

Quick tips:

- Always use the HTML5 doctype.
- Always use double quotes, never single quotes, on attributes.
- Don't include a trailing slash in self-closing elements.
- Don't omit optional closing tags (e.g. `</li>` or `</body>`).
- Avoid generated markup in JavaScript.

Example of well-formed HTML:

```html
<!DOCTYPE html>
<html>
<head>
    <title>Page title</title>
</head>
<body>
    <img src="images/company-logo.png" alt="Company">
    <h1 class="hello-world">Hello, world!</h1>
</body>
</html>
```

### CSS

We generally follow [@mdo's code guide](http://codeguide.co/#css) for CSS, with the exception of four spaces intead of two.

Example of well-formed CSS:

```css
/* Good CSS */
.selector,
.selector-secondary,
.selector[type="text"] {
    padding: 15px;
    margin-bottom: 15px;
    background-color: rgba(0,0,0,.5);
    box-shadow: 0 1px 2px #ccc, inset 0 1px 0 #fff;
}
```

### JavaScript

Postleaf doesn't have a specific JavaScript code guideline, but here are some rules that we try really hard to follow:

- Always use semicolons
- Indent with four spaces (no tabs)
- Always use strict mode
- Keep it pretty. Refrain from using long lines and excessive whitespace.
- Use `$().on('click')` instead of `$().click()` (applies to all event aliases)
- Make sure your work pleases JSHint. There is a Gulp task for this (`gulp jshint`) and it's automatically triggered by `gulp watch`

### PHP

Postleaf doesn't have a specific PHP code guideline, but here are some rules that we try really hard to follow:

- Indent with four spaces (no tabs)
- Keep it pretty. Refrain from using long lines and excesive whitespace.
- Use `camelCase` for method names, not `underscore_naming`.
- Opening braces for methods should be on the same line as the method.

This section is a work in progress. 🔨

## License and Code Contributions

By submitting bug fixes, code, documentation, or anything else to this project, you agree to allow the developer, A Beautiful Site, LLC, to license or relicense your work under their license of choice and you agree to forfeit all copyrights, intellectual property rights, and royalties pertaining to your contribution.

We want Postleaf to remain free for everyone to use, forever, so please do not submit any code or content that is licensed or copyrighted without first getting written consent from the author.