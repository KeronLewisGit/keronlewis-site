---
title: How to Write a Developer Resume That Gets Past ATS
seo_title: Developer Resume for ATS: How to Get Past the Filters
description: How applicant tracking systems read a developer resume, the formatting that parses cleanly, how to match keywords honestly and a checklist to test it.
category: Careers
keyword: developer resume ats
date: 2026-11-10
---

A developer resume gets past an applicant tracking system (ATS) when the software can read it cleanly and a recruiter's search finds it. That means a single-column layout, standard section headings, contact details in the body (not a header or text box), a text-based PDF or DOCX, and the exact technology names from the job post written into your experience.

Most ATS software doesn't read your resume the way a person does. It extracts text into fields (name, email, job titles, dates, skills), then recruiters search and filter those fields. If the parse goes wrong, your Laravel experience might end up in the wrong field or disappear. If the words don't match what the recruiter searches for, you don't show up at all.

I've rewritten my own developer resume for ATS more times than I'd like to admit while applying for remote roles from Trinidad. This is the version of the advice that held up, with the myths taken out.

## What an ATS actually does with your developer resume

An applicant tracking system is the software a company uses to post jobs and manage applicants. Workday, Greenhouse, Lever, iCIMS and many smaller tools all fall in this category. When you upload a resume, three things typically happen:

1. **Parsing.** The system converts your file to text and tries to split it into structured fields: contact details, work history, education, skills.
2. **Storage and search.** Recruiters and hiring managers search the candidate database for terms like "Laravel", "React" or "AWS", and filter by location, title or answers to application questions.
3. **Screening questions.** Many applications include "knockout" questions, such as work authorization or years of experience. A "no" on a required question can remove you before anyone reads the resume.

You'll see confident claims online that most resumes are "auto-rejected by robots". I haven't found a credible source for the popular percentages, and how much a given system filters depends on how each employer configures it. The useful takeaway is narrower: if the parse is broken or the keywords are missing, a human may never see you. Fix those two things and you've done most of the ATS work.

## Formatting rules that keep your resume parseable

Greenhouse publishes a help article on [why a resume fails to parse](https://support.greenhouse.io/hc/en-us/articles/200989175-Unsuccessful-resume-parse). Its list matches what I've seen across other systems: files over 2.5MB, image-only resumes, columned layouts, complex tables, contact details placed in headers, footers or text boxes, graphics, unclear section headings and abbreviated job titles.

Here is the checklist I use:

- [ ] **One column.** No sidebars for skills or contact details.
- [ ] **No tables, text boxes or graphics** for layout. Skill bars and star ratings parse as nothing.
- [ ] **Name, email, phone, city and links in the main body**, at the top, not in the page header.
- [ ] **Standard headings:** Summary, Skills, Experience, Projects, Education, Certifications.
- [ ] **A text-based PDF or a DOCX.** Never a scanned image or a PDF exported as a picture. If you can't select the text, neither can the ATS.
- [ ] **Common fonts** and normal letter spacing. Spaced-out headings like "E X P E R I E N C E" break word matching.
- [ ] **Consistent dates**, for example "Mar 2019 to Jun 2022" or "03/2019 to 06/2022", in the same format everywhere.
- [ ] **Full job titles.** "Senior Software Engineer", not "Sr. SWE".
- [ ] **File under 1MB** and named sensibly: `Firstname-Lastname-Resume.pdf`.

PDF or DOCX is a common question. Both are widely supported. Lever's help center, for example, lists Word, PDF, RTF, HTML and OpenDocument files among the formats its [resume parser](https://help.lever.co/hc/en-us/articles/20087345054749) handles. I send a text-based PDF unless the posting asks for Word, because the layout can't shift on the recruiter's screen. If an application form seems to mangle the PDF when it autofills, upload the DOCX instead.

## The best section order for a developer resume

For most developers with a few years of experience, this order works for both the parser and the person skimming it:

1. **Contact line:** name, city and country, email, phone, LinkedIn, GitHub, portfolio.
2. **Summary:** two or three lines. What you build, with what, and what you're looking for.
3. **Skills:** grouped plain-text lists, for example "Languages: PHP, JavaScript, TypeScript, SQL".
4. **Experience:** reverse chronological, with company, title, location (or "Remote") and dates on their own lines.
5. **Projects:** especially if your job titles undersell what you did.
6. **Education and certifications.**

New graduates and career changers can move Projects above Experience. Keep it to one page if you have under about ten years of experience. Two pages is fine beyond that, as long as page two still earns its place.

## How to match keywords without keyword stuffing

Recruiters search for specific terms, so your resume needs the same words the job post uses. A few rules make this work without turning the page into a word list.

**Mirror the exact names.** If the post says "Laravel", write "Laravel", not only "PHP frameworks". If it says "PostgreSQL", don't rely on "SQL databases".

**Write acronyms both ways once.** "Amazon Web Services (AWS)", "Continuous Integration (CI/CD)". Some searches use the long form and some the short.

**Put skills inside your experience bullets**, not only in the skills list. A recruiter who sees "Laravel" in the skills section and then sees it used in two jobs trusts it more than a bare list.

**Only list what you can talk about in an interview.** The ATS gets you to a person, and the person will ask. Adding Kubernetes because the post mentions it, after one tutorial, tends to backfire in the technical round.

A quick method: paste the job description into a document, highlight every technology, tool and practice it mentions, then check each one against your resume. Add the ones you genuinely have. Leave the rest. If you aren't sure which skills to build next, my list of [in-demand developer skills](/blog/in-demand-developer-skills) is a reasonable starting point.

## Writing experience bullets that work for ATS and humans

Each bullet should say what you built or changed, with which tools, and what happened as a result. A simple pattern:

> Action verb + what you built or changed + technology used + result (number if you have a real one)

A weak bullet versus a stronger one:

| Weak | Stronger |
| --- | --- |
| Worked on payment features | Built WiPay card payments into a Laravel booking system, including webhook handling and automatic receipt emails |
| Responsible for website maintenance | Upgraded client WordPress sites to PHP 8.3 and replaced abandoned plugins with maintained alternatives |
| Used React | Rebuilt the customer dashboard in React and TypeScript, replacing jQuery pages and adding role-based views |

Treat those as templates. Use your own work and your own numbers. Only include a metric if you can explain where it came from. "Cut page load time" with no number is better than a number you made up, because interviewers ask.

Keep bullets to one or two lines, start with a verb, and put the strongest three or four bullets under each role. Older roles can shrink to one or two bullets.

## Projects, GitHub and portfolio links

For developers, links do a lot of the convincing once a human is reading. Make them work for the parser too:

- **Write URLs as plain text**, for example `github.com/yourname`, not only as hyperlinked words. Some parsers drop link targets.
- **Link the strongest two or three projects**, not twenty repositories.
- **Describe each project in one line** with the stack: "Recruitment portal for a security firm: Laravel, MySQL, Alpine.js, document uploads and an HR pipeline."
- **Check the links work** on mobile. Recruiters often skim on a phone.

My [online resume](/resume) follows this structure if you want a working example to compare against.

## Tailoring your resume for each application

Tailoring matters, but a full rewrite per job isn't sustainable. What works for me:

1. Keep a **master resume** with every role, project and bullet you might use.
2. Create **two or three base versions** for the job types you target, for example "Laravel backend", "full-stack PHP/React", "WordPress developer".
3. For each application, **adjust the summary, the skills order and two or three bullets** to match the post. That's ten to fifteen minutes, not an hour.

Some notes if you're applying from the Caribbean to US, UK or Canadian employers:

- **Resume, not a traditional CV.** Many local CVs include a photo, date of birth, marital status, nationality and references. Leave those off for North American and UK applications. Employers there don't expect them, and some prefer not to receive personal details like these.
- **State your location and time zone plainly:** "Port of Spain, Trinidad and Tobago (UTC-4, remote)". Recruiters filter by location and want to know your working-hour overlap.
- **Answer work-authorization questions honestly.** Many remote roles hire only in specific countries, and the knockout question will say so. Look for roles that hire internationally or through employer-of-record services.

I wrote more about finding those roles in [landing remote developer jobs from the Caribbean](/blog/remote-developer-jobs-caribbean).

## Test your resume before you send it

Two minutes of testing catches most parsing problems:

1. **Copy everything from the PDF and paste it into a plain text editor.** If the text comes out in the right order with nothing missing, a parser will probably manage too. If your skills sidebar lands in the middle of a job description, fix the layout.
2. **Watch the autofill.** Many application forms fill in your history from the uploaded file. If job titles, dates or companies land in the wrong boxes, that's what the recruiter's system sees too.
3. **Search it.** Open the file and search for the five most important terms from the job post. If any are missing and you have the skill, add them.
4. **Read it on a phone.** If it's hard to read at that size, it's hard to read for a recruiter between meetings.

## What to do next

Pick one job post you actually want. Run your current resume through the paste test and the keyword check above, fix what breaks, and apply. Then build your two or three base versions from that cleaned-up file so each future application only needs small edits. If you're hiring and want to see how I present my own experience, my [resume](/resume) is online.

## Frequently asked questions

### Should a developer resume be one page or two?

One page suits most developers with under about ten years of experience, because it forces you to keep only the strongest work. Two pages is fine for senior developers with several relevant roles, as long as the most important information sits on page one. ATS software doesn't penalize a second page. Recruiters skimming quickly are the reason to keep it tight.

### Is PDF or Word better for ATS?

Both are widely supported by the main applicant tracking systems. A text-based PDF keeps your layout fixed, so it's my default. The risk is a PDF made from an image or a design tool that flattens text, which parsers can't read. If the job post asks for Word, or an application form garbles your PDF during autofill, upload a DOCX instead.

### Can ATS read resumes with two columns?

Often badly. Greenhouse lists columned layouts among the causes of a failed or partial parse, and other systems have the same problem: text from the two columns can be read across the page and merged. A single-column layout avoids the issue entirely. You can still look modern with good typography, clear headings and spacing.

### Should I put a photo on my developer resume?

Not for applications to US, UK or Canadian employers. Photos don't help parsing, can make a file larger, and some employers prefer not to receive them because of bias concerns. Some local markets in the Caribbean, Europe and elsewhere still expect a photo on a CV. Follow the norms of the country where the employer hires.

### How many keywords should a developer resume have?

There's no target number. Include every technology and practice from the job post that you genuinely have, written the same way the post writes it, and use the important ones in your experience bullets as well as your skills list. Stuffing in terms you can't discuss tends to fail at the interview stage, which wastes everyone's time, including yours.
