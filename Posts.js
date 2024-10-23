import React, { useState, useEffect } from 'react';
import PostLists from './PostLists';
import InfiniteScroll from 'react-infinite-scroll-component';
import Spinner from './Spinner';
import { useLocation } from 'react-router-dom';

const credentials = btoa('admin:Alankar@123#');
const Posts = ({ setProgressBar, post_type = 'posts', setPostIDSinglePage }) => {
    const [posts, setPosts] = useState([]);
    const [page, setPage] = useState(1);
    const [totalRecords, setTotalRecords] = useState(0); // Total number of posts available
    const [loadedRecords, setLoadedRecords] = useState(0); // Number of posts loaded so far

    const location = useLocation(); // Get the current location
    const categoryPattern = /\/category\/([^/]+)/; // Updated regex pattern
    const match = location.pathname.match(categoryPattern); // Match the pattern against the path

    // Extract category name if available
    const categoryName = match ? match[1] : null;
    const categoryMap = {
        'ramayana-epic': '3',
        'mahabharat-epic': '2',
        'short-stories': '4',
        'panchatantra': '435'
    };

    // Reset posts and page when category changes
    useEffect(() => {
        console.log("Category changed. Resetting posts and page.");
        setPosts([]);  // Clear posts
        setPage(1);    // Reset page to 1
        setTotalRecords(0);
        setLoadedRecords(0);
    }, [categoryName]);

    const categories = categoryName ? `&categories=${categoryMap[categoryName] || ''}` : '';
    const exclude_categories = categoryName ? '' : '&categories_exclude=435';

    // Fetch posts whenever the page or category changes
    useEffect(() => {
        const fetchPosts = async () => {
            console.log('Fetching posts for page:', page);

            try {
                setProgressBar(10);
                const url = `http://localhost/sagas_react/wp-json/wp/v2/${post_type}?_fields=id,slug,title,excerpt,featured_media,link,categories&orderby=date&order=desc&per_page=9&page=${page}${categories}${exclude_categories}`;
                console.log('Fetch URL:', url);

                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Basic ${credentials}`,
                        'Content-Type': 'application/json',
                    },
                });

                setProgressBar(30);
                const data = await response.json();
                setProgressBar(50);

                const totalPosts = parseInt(response.headers.get('X-WP-Total'), 10); // Get total post count
                setTotalRecords(totalPosts); // Update total posts count
                setLoadedRecords((prevLoadedRecords) => prevLoadedRecords + data.length); // Track the number of loaded posts
                setProgressBar(70);

                // Append new posts to the existing ones without mutating state
                setPosts((prevPosts) => [...prevPosts, ...data]);

                setProgressBar(100);
            } catch (error) {
                console.error('Error fetching posts:', error);
            }
        };

        fetchPosts();
    }, [page, post_type, categories, exclude_categories, setProgressBar]);

    // Function to load more posts when user scrolls
    const fetchMoreData = () => {
        setPage((prevPage) => prevPage + 1);  // Increment page count for infinite scroll
    };

    return (
        <>
            <InfiniteScroll
                dataLength={posts.length}
                next={fetchMoreData}
                hasMore={loadedRecords < totalRecords} // Check if there are more posts to load
                loader={<Spinner />}
            >
                <div className="row">
                    {posts.map((post) => (
                        <div className="col-md-4 my-2" key={post.id}>
                            <PostLists
                                title={post.title.rendered}
                                slug={post.slug}
                                post_id={post.id}
                                excerpt={post.excerpt.rendered}
                                media={post.featured_media}
                                setPostIDSinglePage={setPostIDSinglePage}
                                post_type={post_type}
                                taxonomy_id={post.categories}
                            />
                        </div>
                    ))}
                </div>
            </InfiniteScroll>
        </>
    );
};

export default Posts;
