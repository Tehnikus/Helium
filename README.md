# Helium Store
Helium store is a deep modification of Opencart 3 with multiple major changes
It introduces comprehensive changes in frontend to have maximum performace, match all modern SEO requirements, support assistive technologies and screen readers, yet use minimum network payload.
TLDR:
- No side JS libraries used to minimize code initialisation, everything is written in Vanilla JS (under 20kb)
- No enormous CSS frameworks, all styles are handwritten (under 15kb)
- Optimized SQL structure and queries
- Caching of static data 
- Modern image formats - WEBP, SVG where needed
- All SEO requirements fulfilled
- Microdata markup in JSON-LD for Google rich snippets for all pages
- FAST loading time, under 100 milliseconds for heaviest page
- Comprehensive all-around support of assistive technologies for disabled users
- SEO Filter with static pages meta-tags, texts, content and so on

## Search engine optimization
SEO Depends on multiple factors, both internal and external. If you are going to take higher place, first thing you do - is to make your pages bulletproof optimized on server side. This includes various code optimizations like remove content clones, hide service data and links, comply SEO requirement related to tags, image formats, responsive design and so on. It can be quite challanging, if you want keep appearence and functional features.
I have an experience of optimizing sites on competetive markets, had multiple succefull projects. So here are part of my server side optimizations, I keep in mind while developing Helium Store:
### Do not hide content
If there is some content that is not displayed under any conditions, it is likely it will be reduced as ranking factor. So no content is hidden or any other way manipulated in Helium Store
### Place content that affect ranking in certain places
The higher the H1 headers and content in code - the better. 
For example, if your main content is way below initial load by Google bot (experimentally it is somewhere after 25kb of code), it will not see your content in first time scanning wich leads to slower indexing. 
So everything user- and Google bot- readable is placed as close to page start as possible. Place headings in order of importance. Make content attractive but not obstructive.
### Make content to code ratio in favor to content
If your page is overflown with code and scripts, it can be treated by Google bot as garbage page. So I used as little markup as possible reaching the point when there is more user readable content, than markup itself. And also removed any inline scripts and styles, so no garbage code anywhere.
### Use semantic markup
Helium store is built on modern semantics, that distinct to Search engines the content, the navigation, the service content and so on.
### Make user interaction convenient
When user interacts with the page that signals to search engines that viewed page is valuable. So interaction should be smooth and convenient. Helium store has no interaction delay, everything is accesible and on the spot.
### Keep all ranking factors filled.
There are multitudes of them, so I'll just mention that all tags are filled in if user didn't in admin part.You have all controls on every significant tag as:
- title
- decription
- H1-H3 headers inside page markup
- ect.
### Remove content cloning
There are couple of challanges related to content cloning. Let's take a look for example on category page and what is done in terms of texts optimization:
- Remove description texts on ordering and filtering pages (except static filters!), pagination, any non-canonical case
- Set titles and H1 according to filter, order and pagination
- Add 'noindex' tag to pages that are partially repeat content of canonical page itself
And so on. So every requirement is fulfilled here

### Create as much static pages with content as you can
Every static page has it's own ranking tags filled, 

### Preload, lazy load, preconnect
There are several types of content that should be preloaded: scripts, styles, images that appear inside of first viewport.
There is content that sould be lazy loaded: Images outside of viewport, unneccesary scripts
### Make your site FAST
Also search bots have time quota for every domain, so if pages load 

## Images
Helium Store uses WEBP and SVG formats by default. The dimensions are lined up in such way to avoid multiple sizes of same image and fit responsive design on various screens at the same time.
But those are completely editable. 
SVG support implemented with proper resizing, so you can use SVG for product options or favicon, elsewhere.

## Database
Every single DB query in Helium store was tested to be as fast as possible to support multiple simultaneous clients browsing store with thouthands of products.
The DB structure changes include new primary keys, indexes, optimized queries and minimization of traffic between SQL and PHP processes.
Slow queries as product search, filtering and displayand others were rewritten from scratch.
Several tables were added for new functions.

## Javascript
To the opposit of common usage of side libraries Helium Store relies on Vanilla JS written from the scratch.
The goal is to create lightweight and fast initializing code that uses native browser features.
Here are some of features included in this project
### Main menu
1. Responsive. 
2. Has assistive tehnologies markup for diasbled users, announces navigation points, supports keyboard interaction, hides closed menu block from screen readers. 
3. Best SEO results - does't hide markup from Google bot as many others.
4. Does not affect time-to-interactive metric.
5. All the data gathered from DB in single query.
### Live Search
1. Displays products with images, prices, short descriptions. 
2. Highlights search terms in text. 
3. Shows countdown to discount end if applicable. 
4. Orders products by relevance to the search term.
5. Has support for assistive technologies and keyboard interaction.
6. Works really fast! Renders products on client side so does not load server with unneccessary requests
In future update I will add search in Blog posts, Product filter pages, Categories, Product features 
### Live cart
The main part of every online store is checkout process. So I focused to make it as easy as possible.
1. The checkout dialog has quick order form, that creates order the same as regular checkout.
2. If customer adds product to cart from category, featured or other product list AND product has required options, the dialog appears with selection product options
3. Live price calculation with neat animation. Price changes accordingly to:
   3.1 Product option that impacts product price (i.e. volume).
   3.2 Batch discounts, like "buy 2 and get 25% off".
   Also uses requestAnimationFrame, so animations do not cause any slowdowns or interactivity decline.
4. Every checkout field and option has support for assistive technologies with announcing errors if occured.
5. Cart does not expose any links or content to Google bot so does not interfrer with SEO results.
6. Shows products number on favicon in browser (Facebook-like message counter) so the customer will not forget to finish checkut.
### Sliders
As for me, sliders are kind of antipattern in modern design, but sometimes they are the only way to introduce product, attract attention and engage users, and also sqeeze a lot of products in relatively small space. So I created really neat slider, that complies SEO, does not hide content, responsive without media queries (yeah, that's possible), relies on browser native features, supports touch and inertial scrolling and initializes for couple of milliseconds.
Used to display product lists like:
- Viewed products
- Featured products
- Discounts
- Latest reviews (under development)
- Related articles from blog
- Related products to other products or blog articles
- Any HTML content (also under development)
- etc.
The biggest point here as Sliders support native browser touch interactions, so no akward behaviour on touch devices. Also perfectly support for keyboard interactions and, consequently, assistive technologies.
### Load more
Load more button fetches products in product lists, articles, comments on products and articles. Works fast, all events (interactivity) attached properly.
### Countdown
Shows countdown to discount end, if the date is set in Admin backoffice. Encourages users for impulsive purchases. Displayed where related product appear: category, live search, related, viewed featured products, product page itself, etc. Has assistive technologies tags, so screen readers will not announce every timer change.
### Anchor nav
It allows to focus user input on certain page blocks, skipping others. Smoothly scrolls to target and shows focus state.
This helps users with assistive technologies and also keeps Google on the page for a bit longer.
Used on category pages, products, blog posts.
If the page has other ranking factors fulfilled, those links will appear on Search Engine Page Results (SERP) as page contents.



## CSS
All the styles were writteh from the scratch, on top of Normalize CSS. It is responsive and also doesn't manipulate content visibility not to harm SEO. 
Minimum mediaqueries used, basically for narrow and wide screens to ommit useless grids for number of screen sizes.
No monstrous CSS frameworks used, as realworld pages use 10%-20% of that code.
### Color system
Design relies heavily on CSS variables and built-in CSS calc() functions.
Calculations are used to dynamically create color schema by using HSL colors.
This means you can quickly change primary color and all other complementary and triad colors will be calculated by CSS.
This also relates to focus states, call-to-action buttons,
Transparency, animations, shadows and rounded corners also controlled in one place, so no need to search and replace multiple lines.
Every bit of design changes accordingly to global variables.
Currently minified and gzipped CSS file is under 10kb of payload.
