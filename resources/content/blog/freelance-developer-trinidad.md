---
title: Freelancing as a Web Developer in Trinidad and Tobago: A Practical Guide
seo_title: Freelance Web Developer in Trinidad: A Practical Guide
description: How to freelance as a web developer in Trinidad and Tobago: BIR registration, quarterly tax, getting paid in USD, pricing, contracts and finding clients.
category: Caribbean Tech
keyword: freelance web developer trinidad
date: 2026-11-10
---

To freelance as a web developer in Trinidad and Tobago, you need a BIR file number, a way to receive foreign payments (Payoneer, a TT PayPal account linked to a Visa debit card, or bank wires into a US-dollar account), a written agreement and deposit for every job, and the discipline to pay income tax quarterly. Registering a business name is optional for a sole trader but makes invoicing and banking easier.

The rest is the same as freelancing anywhere: pick a type of work you can do well, price it so the business survives, and find clients who value it. The local details (forex, WhatsApp, TTD and USD pricing, power cuts) are what make Trinidad different, and that is what this guide focuses on.

I have built websites and web apps from Port of Spain since 2016, both employed and independently, for clients in Trinidad, the wider Caribbean and abroad. This is the practical setup I would give a developer starting out today. I am not an accountant, so treat the tax section as orientation and confirm your own position with the Inland Revenue Division or a qualified adviser.

## Registering as a freelance developer in Trinidad and Tobago

There are two separate registrations people mix up.

**1. BIR file number (required).** The [Inland Revenue Division](https://www.ird.gov.tt/self-employed-sole-trader) says self-employed people and sole traders must register for a Board of Inland Revenue file number, which you can do through e-Tax. If you were ever employed in Trinidad, you probably already have one. This is what you pay tax under.

**2. Business name registration (optional for most sole traders).** If you trade under a name other than your own, such as "Lewis Web Studio" instead of "Keron Lewis", you register that business name with the Companies Registry, which can be done online. Fees are modest but change, so check the current schedule on the Registry or TTBizLink before paying. A registered name helps when opening a business bank account and makes your invoices look more established to corporate clients.

**What about a limited company?** Most solo freelancers do not need one at the start. A company adds filings, annual returns and accounting costs. It starts to make sense when you hire staff, take on contracts with real liability, or a client insists on it. Talk to an accountant before you incorporate.

**NIS.** According to the [National Insurance Board's FAQ](https://nibtt.net/faq/cont_FAQs.html), the system does not yet cover the self-employed, although a self-employed person who was previously insured may pay voluntary contributions. If you have contributions from earlier employment, ask NIB about voluntary contributions so you do not lose ground on your pension record. This has been under discussion for years, so check whether it has changed.

**VAT.** You must register for VAT once your sales exceed, or are expected to exceed, TT$500,000 in a 12-month period. Most new freelancers are well below that. Note that VAT treatment of services exported to foreign clients is its own question, so get advice before you cross the threshold.

## Taxes for self-employed developers in Trinidad

The [IRD's self-employed page](https://www.ird.gov.tt/self-employed-sole-trader) sets out the main obligations:

| Obligation | What it is | When |
|---|---|---|
| Income tax | 25% of chargeable profits up to TT$1,000,000, 30% on the portion above | Paid in quarterly instalments |
| Business levy | 0.6% of gross sales or receipts, with an exemption for the first three years after registration and other conditions | Quarterly |
| Health surcharge | TT$8.25 a week if average monthly receipts exceed TT$469.99, otherwise TT$4.80 | Quarterly |
| Annual return | Individual income tax return for the year | Once a year, by the IRD's published deadline |
| Records | Proper books and records of income and expenses | Always |

Quarterly payments fall due at the end of March, June, September and December. Chargeable profit means your receipts minus allowable business expenses, and resident individuals also get a personal allowance (TT$90,000 at the time of writing, but budgets change it, so check the current figure).

Practical habits that save trouble:

- **Open a separate account for business money**, even if it is just a second personal account at first. Mixing it with personal spending makes record keeping miserable.
- **Move a fixed share of every payment into a tax account** the day it arrives. When the quarterly payment is due, the money is already there.
- **Keep receipts for business expenses**: laptop, software subscriptions, hosting, internet, a share of electricity if you work from home. Ask your adviser what is allowable.
- **Record the TTD value of foreign payments** on the date you receive them, using the rate your bank applied. You will need consistent figures for your return.
- **If you pay overseas contractors**, read the IRD's note on withholding tax for payments sent out of Trinidad and Tobago before you do it.

## How to get paid by foreign clients from Trinidad

This is the question I get asked most, and the honest answer is that there is no single perfect option. Here is how the main routes compare, based on what I could confirm at the time of writing.

| Method | Works for TT residents | How the money reaches you | Watch out for |
|---|---|---|---|
| Payoneer | Yes, with direct registration and KYC | Withdraw to a local bank account | Fees and exchange rate on withdrawal; check in your account |
| PayPal | Yes, with a Trinidad and Tobago PayPal account | Withdraw to a linked Visa debit or credit card, then to your bank | Receiving fees, conversion spreads, possible holds on new accounts |
| International wire (SWIFT) | Yes | Directly into your bank, ideally a USD account | Sending and receiving bank fees, intermediary charges |
| Client pays via Wise | Client sends from a supported country | Lands in your TT bank account in TTD; you do not need a Wise account | Converted to TTD on their side, so you lose the USD |
| Stripe | Not directly | Needs a business registered in a supported country, such as a US LLC | Setup cost and ongoing compliance; overkill for most |
| WiPay | Yes, a local gateway | Card payments from clients into your WiPay account | Mainly for local and regional card payments |

My view:

- For **platform work** (Upwork, Fiverr and similar), Payoneer is usually the cleanest route, and many platforms support it as a payout method.
- For **direct foreign clients** on a retainer, a wire into a **USD account** at a local bank or credit union is simple and keeps the money in US dollars. Ask your bank what they charge to receive.
- **PayPal** is convenient for small one-off jobs, but read its current fee schedule. A [Trinidad Guardian guide from March 2026](https://guardian.co.tt/business/how-to-get-paid-on-paypal-stripe--payoneer-in-tt-6.2.2530731.f2903d5eb1) lists which local Visa debit cards it found accepted for withdrawals.
- For **local clients**, bank transfer and card payments through a local gateway like WiPay are the norm. If you build sites for local businesses, learning to integrate WiPay pays for itself; I cover it in [How to Integrate WiPay Payments in Laravel](/blog/wipay-laravel-integration).

Why keep money in USD? Trinidad's banks have repeatedly cut the US-dollar limits on personal credit cards because of the foreign exchange shortage. Your hosting, domains, software and AI tools are billed in USD. Earning in USD and paying for tools from USD is far less stressful than fighting a card limit every month.

## Pricing your work in TTD and USD

I will not quote market rates here because they vary widely with experience, client and scope, and made-up averages help nobody. Instead, a method:

1. **Work out your monthly cost of being in business**: living expenses, tax, NIS voluntary contributions if any, equipment replacement, software, internet, electricity, and a buffer for slow months.
2. **Estimate realistic billable hours.** Not 160 a month. Admin, sales, learning and unpaid revisions take a big share. Many freelancers bill far fewer hours than they work.
3. **Divide** to get a floor hourly rate. Price projects above it, not at it.
4. **Quote local clients in TTD and foreign clients in USD.** Do not convert your local price to USD for foreign clients; they are paying for the same skill in a different market.
5. **Price projects, not hours, when the scope is clear.** A five-page brochure site with a contact form is a known quantity. Hourly billing makes more sense for maintenance and open-ended work.
6. **Add recurring work.** Hosting management, updates, backups and small changes on a monthly retainer smooth out the gaps between projects.

Always take a **deposit** before starting. Fifty percent up front is common for fixed-price builds, with the rest on completion and before the site goes live on the client's domain.

## Contracts, scope and getting paid on time

Most freelance pain in Trinidad is not technical. It is a client who keeps adding pages, goes quiet for two months, or pays the final invoice "next week" for half a year. A short written agreement prevents most of it. It does not need to be a lawyer's document, but it should cover:

- [ ] What is included, page by page or feature by feature
- [ ] What is not included (content writing, photos, copywriting, logo design)
- [ ] Number of revision rounds
- [ ] Timeline, and what happens to it if the client is late with content
- [ ] Price, currency, deposit and payment milestones
- [ ] Who owns the code and design after final payment
- [ ] Who pays for and owns the domain, hosting and third-party licences
- [ ] Ongoing support: what it costs and what response time you offer
- [ ] How either side can end the agreement

WhatsApp is how most local clients will want to communicate. That is fine for quick questions, but confirm decisions about scope or price by email, so you have a record you can find later.

## Finding freelance web development clients

**Locally**, the work is mostly word of mouth. Businesses ask people they trust. Things that help:

- A portfolio of real, live sites rather than screenshots. Two or three good ones is enough to start.
- Being easy to reach on WhatsApp and replying the same day.
- Targeting businesses whose sites are clearly outdated, slow or broken on mobile, with a specific suggestion rather than a generic pitch.
- Partnering with graphic designers, marketers and photographers who get asked for websites but do not build them.

**Abroad**, Trinidad's time zone is a real advantage. We are on UTC-4 all year, which lines up with US Eastern time for much of the year and overlaps reasonably with Canada and the UK in the mornings. Remote contracts and part-time roles are often steadier than one-off freelance gigs. I wrote separately about [landing remote developer jobs from the Caribbean](/blog/remote-developer-jobs-caribbean), and a résumé that gets through screening software matters even for contract roles; see [How to Write a Developer Resume That Gets Past ATS](/blog/developer-resume-ats).

## Your working setup: power, internet and hurricane season

Clients abroad do not care that there was a power cut in your area. Plan for it:

- **A UPS** for your router and modem, so a short outage does not drop you off a call.
- **A mobile data backup** you can tether to. Keep enough data on the plan to finish a day's work.
- **Laptop over desktop** if you can, for the built-in battery.
- **Off-site backups** of client work and credentials, in a password manager and cloud storage, not only on your machine.
- **Hurricane season**, June to November, rarely hits Trinidad directly but brings storms, flooding and outages. Tell regular clients your backup plan before they need it.

If you deploy to low-cost shared hosting, as many local clients prefer, you will also want a reliable deployment routine. I documented mine in [How to Deploy Laravel on Shared Hosting (Even Without SSH)](/blog/deploy-laravel-shared-hosting).

## Your first month as a freelancer in Trinidad

1. Confirm your BIR file number and set up e-Tax access.
2. Decide whether to register a business name, and do it if you will invoice under one.
3. Open a separate account for business income, and a USD account if you will have foreign clients.
4. Set up Payoneer, and a TT PayPal account linked to an accepted Visa debit card, so you can accept both.
5. Write a one-page agreement template and an invoice template with your deposit terms.
6. Put two or three of your best live projects on a simple portfolio page.
7. Put a fixed share of every payment into a tax account and calendar the quarterly due dates.
8. Contact ten businesses or agencies with a specific, useful observation about their site.

If you want to see how I present my own work and experience, my [résumé](/resume) and [Code Canvas case study](/work/code-canvas) are examples you can borrow structure from.

## Frequently asked questions

### Do freelancers have to pay tax in Trinidad and Tobago?

Yes. Self-employed people must register for a BIR file number, keep proper records and pay income tax on chargeable profits, at 25% up to TT$1,000,000 and 30% above that, after allowances and deductions. They also pay health surcharge and possibly business levy, generally in quarterly instalments, and file an annual individual return. Confirm current rates and allowances with the Inland Revenue Division.

### Does Payoneer work in Trinidad and Tobago?

Yes. Trinidad and Tobago residents can register for Payoneer directly, complete identity verification, receive payments from clients and freelance platforms, and withdraw to a local bank account. Check the withdrawal fees and exchange rate shown in your account before choosing between withdrawing in USD or TTD, and confirm your bank's charges for receiving the transfer.

### How do I receive PayPal payments in Trinidad?

Open a PayPal account registered to Trinidad and Tobago, then link a Visa debit or credit card that PayPal accepts for withdrawals, since Mastercard cannot receive them. You withdraw your balance to that card and then into your bank. Expect receiving fees and currency conversion spreads, and possible holds on a new account until it has some transaction history.

### Do I need to register a business to freelance in Trinidad?

Not always. You must have a BIR file number to pay tax as a self-employed person, but you only need to register a business name if you trade under a name other than your own. Many sole traders do register one because it helps with invoicing and business banking. A limited company is usually unnecessary until you hire staff or take on larger contracts.

### Does NIS cover self-employed people in Trinidad?

According to the National Insurance Board's own FAQ, the National Insurance System does not yet cover self-employed persons, so freelancers are not required to contribute. If you were previously insured through employment, you may be able to pay voluntary contributions to protect your record. Extending coverage to the self-employed has been discussed for years, so check with NIB for any change.
